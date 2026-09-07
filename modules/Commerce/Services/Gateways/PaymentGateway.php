<?php

namespace Modules\Commerce\Services\Gateways;

use CodeIgniter\HTTP\IncomingRequest;

/**
 * A way of taking money.
 *
 * Four implementations, and the shape is the same for all of them because the
 * one rule that matters is the same for all of them: **the browser coming back
 * never proves anything**. `begin()` sends the buyer somewhere; `verifyWebhook()`
 * is the only method whose word is taken, and it must verify a signature before
 * it returns anything at all.
 *
 * A gateway with no keys configured is not offered at checkout. That is
 * deliberate: half-configured payment routes fail at the worst moment and in
 * the least legible way, and a site that quietly falls back to "contact us"
 * after taking somebody through a checkout has lost them. `isConfigured()` is
 * checked before a button is drawn, not after it is pressed.
 */
interface PaymentGateway
{
    /** The key this gateway is stored and referred to by: stripe, payhere, paypal, bank. */
    public function key(): string;

    /** What the buyer sees on the button. */
    public function label(): string;

    /**
     * Whether this gateway has everything it needs. False means it is not
     * offered at all — no button, no half-working flow.
     */
    public function isConfigured(): bool;

    /** Which currencies it can actually take. */
    public function supports(string $currency): bool;

    /**
     * Start a payment for an order.
     *
     * @return array{mode:string, url?:string, fields?:array, action?:string, message?:string}
     *         mode ∈ redirect | post | instructions
     *         - redirect: send the browser to `url`
     *         - post:     auto-submit `fields` to `action`
     *         - instructions: show `message` (bank transfer)
     */
    public function begin(array $order, array $items, string $returnUrl, string $cancelUrl): array;

    /**
     * Verify an incoming webhook and, if it is genuine and says the money
     * arrived, describe the payment.
     *
     * Returns null for anything it cannot verify. Never throw on a bad
     * signature: a webhook endpoint that 500s on rubbish is a webhook endpoint
     * somebody will use to fill the error log.
     *
     * @return array{order_no:string, gateway_ref:string, amount_cents:int, currency:string, status:string}|null
     */
    public function verifyWebhook(IncomingRequest $request): ?array;
}
