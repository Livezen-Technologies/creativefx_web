<?php

namespace Modules\Commerce\Services\Gateways;

/**
 * Which ways of paying exist, and which of them this buyer may actually use.
 *
 * The routing rule from the blueprint, in one place: LKR goes to PayHere,
 * everything else to Stripe, with PayPal as the alternate and bank transfer
 * always available. Spreading that across the checkout view and three
 * controllers is how a site ends up offering a Sri Lankan buyer a Stripe button
 * that cannot take their currency.
 *
 * A gateway that is not configured is not returned. There is no half-configured
 * state to fall through: the button is either there and works, or it is not
 * there.
 */
class GatewayRegistry
{
    /** @return list<PaymentGateway> */
    public static function all(): array
    {
        return [
            new PayHereGateway(),
            new StripeGateway(),
            new PayPalGateway(),
            new BankTransferGateway(),
        ];
    }

    public static function find(string $key): ?PaymentGateway
    {
        foreach (self::all() as $gateway) {
            if ($gateway->key() === $key) {
                return $gateway;
            }
        }

        return null;
    }

    /**
     * The gateways offered for one currency, in the order they should appear.
     *
     * Bank transfer is deliberately last. It works, and for a company it is
     * often the right answer, but it is the slowest route to a confirmed seat
     * and should not be the first thing an individual buyer sees.
     *
     * @return list<PaymentGateway>
     */
    public static function forCurrency(string $currency): array
    {
        $enabled = array_filter(
            explode(',', (string) setting('enabled', 'payhere,stripe,paypal,bank', 'payments')),
            static fn (string $k): bool => trim($k) !== ''
        );
        $enabled = array_map('trim', $enabled);

        $out = [];
        foreach (self::all() as $gateway) {
            if (! in_array($gateway->key(), $enabled, true)) {
                continue;
            }
            if (! $gateway->isConfigured() || ! $gateway->supports($currency)) {
                continue;
            }
            $out[] = $gateway;
        }

        return $out;
    }
}
