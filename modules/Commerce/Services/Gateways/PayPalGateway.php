<?php

namespace Modules\Commerce\Services\Gateways;

use CodeIgniter\HTTP\IncomingRequest;

/**
 * PayPal, as the alternate global route.
 *
 * It earns its place as a trust signal rather than as a cheaper rail: in
 * several markets a buyer who will not give a card to an unfamiliar training
 * school will still pay through PayPal, and that is the whole reason to carry a
 * second global gateway.
 *
 * Webhook verification is done by asking PayPal, which is how PayPal works:
 * unlike Stripe there is no shared secret to HMAC against, so the transmission
 * headers and the raw body are posted back to their verification endpoint and
 * the answer is trusted only when it says SUCCESS. That means a network failure
 * during verification is treated as *not verified* — the delivery is left
 * unacknowledged so PayPal retries, rather than fulfilled on the assumption
 * that it was probably genuine.
 */
class PayPalGateway implements PaymentGateway
{
    public function key(): string
    {
        return 'paypal';
    }

    public function label(): string
    {
        return lang('Commerce.gateway.paypal');
    }

    public function isConfigured(): bool
    {
        return $this->clientId() !== '' && $this->secret() !== '' && $this->webhookId() !== '';
    }

    public function supports(string $currency): bool
    {
        return ! in_array(strtoupper($currency), ['LKR'], true);
    }

    public function begin(array $order, array $items, string $returnUrl, string $cancelUrl): array
    {
        $token = $this->accessToken();
        if ($token === null) {
            return ['mode' => 'instructions', 'message' => lang('Commerce.gateway.unavailable')];
        }

        $response = service('curlrequest')->post($this->api() . '/v2/checkout/orders', [
            'headers'     => ['Authorization' => 'Bearer ' . $token, 'Content-Type' => 'application/json'],
            'body'        => json_encode([
                'intent'         => 'CAPTURE',
                'purchase_units' => [[
                    // Our order number, echoed back on the webhook. Without it
                    // a captured payment cannot be matched to anything.
                    'custom_id'  => $order['order_no'],
                    'invoice_id' => $order['order_no'],
                    'amount'     => [
                        'currency_code' => strtoupper((string) $order['currency']),
                        'value'         => number_format((int) $order['total_cents'] / 100, 2, '.', ''),
                    ],
                ]],
                'application_context' => [
                    'brand_name'  => (string) setting('site_name', 'MyLearnPlus'),
                    'user_action' => 'PAY_NOW',
                    'return_url'  => $returnUrl,
                    'cancel_url'  => $cancelUrl,
                ],
            ]),
            'http_errors' => false,
            'timeout'     => 20,
        ]);

        $body = json_decode((string) $response->getBody(), true) ?: [];
        foreach ($body['links'] ?? [] as $link) {
            if (($link['rel'] ?? '') === 'approve') {
                return ['mode' => 'redirect', 'url' => (string) $link['href']];
            }
        }

        log_message('error', 'PayPal order creation failed: {b}', ['b' => (string) $response->getBody()]);

        return ['mode' => 'instructions', 'message' => lang('Commerce.gateway.unavailable')];
    }

    public function verifyWebhook(IncomingRequest $request): ?array
    {
        $payload = $request->getBody() ?? '';
        if ($payload === '') {
            return null;
        }

        $token = $this->accessToken();
        if ($token === null) {
            return null;
        }

        $verify = service('curlrequest')->post($this->api() . '/v1/notifications/verify-webhook-signature', [
            'headers'     => ['Authorization' => 'Bearer ' . $token, 'Content-Type' => 'application/json'],
            'body'        => json_encode([
                'auth_algo'         => $request->getHeaderLine('PayPal-Auth-Algo'),
                'cert_url'          => $request->getHeaderLine('PayPal-Cert-Url'),
                'transmission_id'   => $request->getHeaderLine('PayPal-Transmission-Id'),
                'transmission_sig'  => $request->getHeaderLine('PayPal-Transmission-Sig'),
                'transmission_time' => $request->getHeaderLine('PayPal-Transmission-Time'),
                'webhook_id'        => $this->webhookId(),
                // The event has to go back as a structure, not as the raw
                // string: PayPal re-serialises it their way before checking.
                'webhook_event'     => json_decode($payload, true),
            ]),
            'http_errors' => false,
            'timeout'     => 20,
        ]);

        $result = json_decode((string) $verify->getBody(), true) ?: [];
        if (($result['verification_status'] ?? '') !== 'SUCCESS') {
            return null;
        }

        $event = json_decode($payload, true) ?: [];
        if (($event['event_type'] ?? '') !== 'PAYMENT.CAPTURE.COMPLETED') {
            return null;
        }

        $resource = $event['resource'] ?? [];
        $orderNo  = (string) ($resource['custom_id'] ?? $resource['invoice_id'] ?? '');
        if ($orderNo === '') {
            return null;
        }

        return [
            'order_no'     => $orderNo,
            'gateway_ref'  => (string) ($resource['id'] ?? ''),
            'amount_cents' => (int) round(((float) ($resource['amount']['value'] ?? 0)) * 100),
            'currency'     => strtoupper((string) ($resource['amount']['currency_code'] ?? '')),
            'status'       => 'paid',
        ];
    }

    private function accessToken(): ?string
    {
        // Cached for slightly less than PayPal's stated nine hours: a token
        // fetched on every webhook would double the latency of every delivery
        // and count against their rate limit for no reason.
        $cache = cache();
        $key   = 'paypal_token_' . md5($this->clientId() . $this->api());
        $hit   = $cache->get($key);
        if (is_string($hit) && $hit !== '') {
            return $hit;
        }

        $response = service('curlrequest')->post($this->api() . '/v1/oauth2/token', [
            'auth'        => [$this->clientId(), $this->secret()],
            'form_params' => ['grant_type' => 'client_credentials'],
            'http_errors' => false,
            'timeout'     => 20,
        ]);

        $body  = json_decode((string) $response->getBody(), true) ?: [];
        $token = (string) ($body['access_token'] ?? '');
        if ($token === '') {
            log_message('error', 'PayPal token request failed: {b}', ['b' => (string) $response->getBody()]);

            return null;
        }

        $cache->save($key, $token, max(60, (int) ($body['expires_in'] ?? 3600) - 300));

        return $token;
    }

    private function api(): string
    {
        return setting('paypal_sandbox', '1', 'payments') === '1'
            ? 'https://api-m.sandbox.paypal.com'
            : 'https://api-m.paypal.com';
    }

    private function clientId(): string
    {
        return trim((string) setting('paypal_client_id', '', 'payments'));
    }

    private function secret(): string
    {
        return trim((string) setting('paypal_secret', '', 'payments'));
    }

    private function webhookId(): string
    {
        return trim((string) setting('paypal_webhook_id', '', 'payments'));
    }
}
