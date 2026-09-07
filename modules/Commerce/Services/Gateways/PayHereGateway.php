<?php

namespace Modules\Commerce\Services\Gateways;

use CodeIgniter\HTTP\IncomingRequest;

/**
 * PayHere, for the Sri Lankan LKR market: local cards, bank and mobile wallets.
 *
 * PayHere is a form POST rather than an API call — the browser is submitted
 * straight to their checkout with a hash that proves the amount came from us —
 * so `begin()` returns fields to auto-submit rather than a URL to follow.
 *
 * Both hashes below are MD5 because PayHere specifies MD5. That is their
 * protocol, not a choice: the hash is an integrity check on values we also
 * store, not a password, and the shared secret is what actually authenticates
 * it. The merchant secret is hashed and upper-cased before use exactly as their
 * documentation requires, and getting either the order or the case wrong
 * produces a silent mismatch rather than an error, which is why it is written
 * out in one place here and never inlined.
 */
class PayHereGateway implements PaymentGateway
{
    public function key(): string
    {
        return 'payhere';
    }

    public function label(): string
    {
        return lang('Commerce.gateway.payhere');
    }

    public function isConfigured(): bool
    {
        return $this->merchantId() !== '' && $this->merchantSecret() !== '';
    }

    public function supports(string $currency): bool
    {
        return in_array(strtoupper($currency), ['LKR', 'USD'], true);
    }

    public function begin(array $order, array $items, string $returnUrl, string $cancelUrl): array
    {
        $billing = json_decode((string) $order['billing_json'], true) ?: [];
        $amount  = $this->amount($order);
        $name    = trim((string) ($billing['name'] ?? ''));
        $parts   = preg_split('/\s+/', $name, 2) ?: [];

        return [
            'mode'   => 'post',
            'action' => $this->sandbox()
                ? 'https://sandbox.payhere.lk/pay/checkout'
                : 'https://www.payhere.lk/pay/checkout',
            'fields' => [
                'merchant_id' => $this->merchantId(),
                'return_url'  => $returnUrl,
                'cancel_url'  => $cancelUrl,
                'notify_url'  => base_url('webhooks/payhere'),
                'order_id'    => (string) $order['order_no'],
                'items'       => mb_substr($this->describe($items), 0, 100),
                'currency'    => strtoupper((string) $order['currency']),
                'amount'      => $amount,
                'first_name'  => mb_substr($parts[0] ?? '', 0, 50),
                'last_name'   => mb_substr($parts[1] ?? '', 0, 50),
                'email'       => (string) ($billing['email'] ?? ''),
                'phone'       => (string) ($billing['phone'] ?? ''),
                'address'     => mb_substr((string) ($billing['address'] ?? ''), 0, 100),
                'city'        => mb_substr((string) ($billing['city'] ?? ''), 0, 50),
                'country'     => (string) ($billing['country'] ?? 'Sri Lanka'),
                'hash'        => $this->requestHash((string) $order['order_no'], $amount, strtoupper((string) $order['currency'])),
            ],
        ];
    }

    public function verifyWebhook(IncomingRequest $request): ?array
    {
        $merchantId = (string) $request->getPost('merchant_id');
        $orderNo    = (string) $request->getPost('order_id');
        $amount     = (string) $request->getPost('payhere_amount');
        $currency   = (string) $request->getPost('payhere_currency');
        $statusCode = (string) $request->getPost('status_code');
        $sig        = strtoupper((string) $request->getPost('md5sig'));

        if ($orderNo === '' || $sig === '') {
            return null;
        }

        $expected = strtoupper(md5(
            $merchantId . $orderNo . $amount . $currency . $statusCode
            . strtoupper(md5($this->merchantSecret()))
        ));

        if (! hash_equals($expected, $sig)) {
            return null;
        }

        // 2 is success. Anything else — pending, cancelled, chargeback — is not
        // an enrolment, and is recorded by the controller rather than acted on.
        if ($statusCode !== '2') {
            return null;
        }

        return [
            'order_no'     => $orderNo,
            'gateway_ref'  => (string) ($request->getPost('payment_id') ?: $orderNo),
            // PayHere sends a decimal string. It is turned into minor units
            // once, here, and never handled as a float anywhere else.
            'amount_cents' => (int) round(((float) $amount) * 100),
            'currency'     => strtoupper($currency),
            'status'       => 'paid',
        ];
    }

    /** PayHere requires the amount formatted to exactly two decimals. */
    private function amount(array $order): string
    {
        return number_format((int) $order['total_cents'] / 100, 2, '.', '');
    }

    private function requestHash(string $orderNo, string $amount, string $currency): string
    {
        return strtoupper(md5(
            $this->merchantId() . $orderNo . $amount . $currency
            . strtoupper(md5($this->merchantSecret()))
        ));
    }

    /** @param list<array> $items */
    private function describe(array $items): string
    {
        $first = (string) ($items[0]['title_snapshot'] ?? lang('Commerce.gateway.training'));
        $more  = count($items) - 1;

        return $more > 0 ? $first . ' + ' . $more . ' more' : $first;
    }

    private function merchantId(): string
    {
        return trim((string) setting('payhere_merchant_id', '', 'payments'));
    }

    private function merchantSecret(): string
    {
        return trim((string) setting('payhere_merchant_secret', '', 'payments'));
    }

    private function sandbox(): bool
    {
        return setting('payhere_sandbox', '1', 'payments') === '1';
    }
}
