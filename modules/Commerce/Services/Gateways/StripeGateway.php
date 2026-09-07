<?php

namespace Modules\Commerce\Services\Gateways;

use CodeIgniter\HTTP\IncomingRequest;

/**
 * Stripe Checkout, for the global USD market.
 *
 * Hosted Checkout rather than card fields on this site, so no card number ever
 * touches this server and the PCI scope stays at SAQ-A. That is not a
 * convenience: taking card details in our own form would make every future
 * change to the checkout a compliance question.
 *
 * The webhook signature is verified by hand rather than with Stripe's SDK,
 * which is a dependency this project does not otherwise need. The scheme is
 * simple and stable: the `Stripe-Signature` header carries a timestamp and one
 * or more v1 signatures, each an HMAC-SHA256 of "timestamp.payload" keyed with
 * the endpoint's signing secret.
 */
class StripeGateway implements PaymentGateway
{
    /** How far out of step with Stripe's clock a webhook may be, in seconds. */
    private const TOLERANCE = 300;

    public function key(): string
    {
        return 'stripe';
    }

    public function label(): string
    {
        return lang('Commerce.gateway.stripe');
    }

    public function isConfigured(): bool
    {
        return $this->secretKey() !== '' && $this->webhookSecret() !== '';
    }

    public function supports(string $currency): bool
    {
        // LKR is not a Stripe presentment currency, so a Sri Lankan buyer is
        // routed to PayHere rather than shown a button that will fail.
        return ! in_array(strtoupper($currency), ['LKR'], true);
    }

    public function begin(array $order, array $items, string $returnUrl, string $cancelUrl): array
    {
        $billing = json_decode((string) $order['billing_json'], true) ?: [];

        // Stripe wants the whole basket, but the amounts must add up to the
        // order total exactly. Rather than re-deriving line prices — and
        // risking a rounding difference between our arithmetic and theirs —
        // the order is sent as one line at the total that was agreed.
        $form = [
            'mode'                                  => 'payment',
            'success_url'                           => $returnUrl,
            'cancel_url'                            => $cancelUrl,
            'client_reference_id'                   => $order['order_no'],
            'customer_email'                        => $billing['email'] ?? null,
            'metadata[order_no]'                    => $order['order_no'],
            'line_items[0][quantity]'               => 1,
            'line_items[0][price_data][currency]'   => strtolower((string) $order['currency']),
            'line_items[0][price_data][unit_amount]' => (int) $order['total_cents'],
            'line_items[0][price_data][product_data][name]' => $this->describe($items),
        ];

        $response = service('curlrequest')->post('https://api.stripe.com/v1/checkout/sessions', [
            'headers'     => ['Authorization' => 'Bearer ' . $this->secretKey()],
            'form_params' => array_filter($form, static fn ($v) => $v !== null),
            'http_errors' => false,
            'timeout'     => 20,
        ]);

        $body = json_decode((string) $response->getBody(), true) ?: [];

        if (empty($body['url'])) {
            log_message('error', 'Stripe checkout session failed: {b}', ['b' => (string) $response->getBody()]);

            return ['mode' => 'instructions', 'message' => lang('Commerce.gateway.unavailable')];
        }

        return ['mode' => 'redirect', 'url' => (string) $body['url']];
    }

    public function verifyWebhook(IncomingRequest $request): ?array
    {
        $payload = $request->getBody() ?? '';
        $header  = $request->getHeaderLine('Stripe-Signature');
        if ($payload === '' || $header === '') {
            return null;
        }

        $parts = [];
        foreach (explode(',', $header) as $pair) {
            [$k, $v] = array_pad(explode('=', trim($pair), 2), 2, '');
            $parts[$k][] = $v;
        }

        $timestamp = (int) ($parts['t'][0] ?? 0);
        // A replayed webhook from last month must not enrol anybody today.
        if ($timestamp === 0 || abs(time() - $timestamp) > self::TOLERANCE) {
            return null;
        }

        $expected = hash_hmac('sha256', $timestamp . '.' . $payload, $this->webhookSecret());
        $ok       = false;
        foreach ($parts['v1'] ?? [] as $candidate) {
            // Constant-time, so the comparison cannot be turned into an oracle
            // for guessing the signature one byte at a time.
            $ok = $ok || hash_equals($expected, $candidate);
        }
        if (! $ok) {
            return null;
        }

        $event = json_decode($payload, true) ?: [];
        if (($event['type'] ?? '') !== 'checkout.session.completed') {
            return null;
        }

        $object = $event['data']['object'] ?? [];
        if (($object['payment_status'] ?? '') !== 'paid') {
            return null;
        }

        $orderNo = (string) ($object['client_reference_id'] ?? $object['metadata']['order_no'] ?? '');
        if ($orderNo === '') {
            return null;
        }

        return [
            'order_no'     => $orderNo,
            'gateway_ref'  => (string) ($object['payment_intent'] ?? $object['id'] ?? ''),
            'amount_cents' => (int) ($object['amount_total'] ?? 0),
            'currency'     => strtoupper((string) ($object['currency'] ?? '')),
            'status'       => 'paid',
        ];
    }

    /** @param list<array> $items */
    private function describe(array $items): string
    {
        $first = $items[0]['title_snapshot'] ?? lang('Commerce.gateway.training');
        $more  = count($items) - 1;

        return $more > 0 ? $first . ' + ' . $more . ' more' : (string) $first;
    }

    private function secretKey(): string
    {
        return trim((string) setting('stripe_secret', '', 'payments'));
    }

    private function webhookSecret(): string
    {
        return trim((string) setting('stripe_webhook_secret', '', 'payments'));
    }
}
