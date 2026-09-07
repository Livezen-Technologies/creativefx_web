<?php

namespace Modules\Commerce\Services\Gateways;

use CodeIgniter\HTTP\IncomingRequest;

/**
 * Bank transfer and invoice.
 *
 * The only route that needs no merchant account, which is why it exists: the
 * school can take a real booking on the day the site goes live, before Stripe
 * and PayHere have finished their onboarding. It is also what corporate buyers
 * actually want — a finance department pays against an invoice, not a card.
 *
 * There is no webhook. The order sits at `pending_payment` with its seats held
 * to the due date, and a human in the admin marks it received when the money
 * lands. That is not a limitation to apologise for: it is how business-to-
 * business payment works, and pretending otherwise would mean either
 * enrolling somebody who has not paid or making them wait for a reconciliation
 * that no API is going to tell us about.
 */
class BankTransferGateway implements PaymentGateway
{
    public function key(): string
    {
        return 'bank';
    }

    public function label(): string
    {
        return lang('Commerce.gateway.bank');
    }

    /** Always available — that is the point of it. */
    public function isConfigured(): bool
    {
        return true;
    }

    public function supports(string $currency): bool
    {
        return true;
    }

    public function begin(array $order, array $items, string $returnUrl, string $cancelUrl): array
    {
        return [
            'mode'    => 'instructions',
            'url'     => $returnUrl,
            'message' => (string) setting('bank_instructions', '', 'payments'),
        ];
    }

    public function verifyWebhook(IncomingRequest $request): ?array
    {
        // No bank sends a webhook. Confirmation is a person in the admin.
        return null;
    }
}
