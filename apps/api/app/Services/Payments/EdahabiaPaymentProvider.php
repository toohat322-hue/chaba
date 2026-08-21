<?php

namespace App\Services\Payments;

use App\Exceptions\ApiException;
use App\Models\Order;
use App\Models\Payment;

/**
 * PRD §7.9/§25 Phase 6: Edahabia (Algérie Poste) online payment gateway.
 * No merchant credentials or Edahabia API documentation exist yet, so every
 * method here fails clearly instead of guessing at endpoints/signatures —
 * this class only wires the configuration/interface shape so the real
 * integration becomes a drop-in once EDAHABIA_* env vars and actual API
 * docs are available (see .env.example and config/services.php).
 */
class EdahabiaPaymentProvider implements PaymentProviderInterface
{
    public function initiate(Order $order): Payment
    {
        $this->assertConfigured();

        // TODO(payments/edahabia): call Edahabia's real transaction-
        // initiation endpoint once merchant onboarding + API docs exist.
        throw ApiException::businessRule('payment_gateway_not_implemented', 'Edahabia online payment is not yet available.');
    }

    public function verify(string $transactionRef): Payment
    {
        $this->assertConfigured();

        // TODO(payments/edahabia): verify transaction status against Edahabia's API.
        throw ApiException::businessRule('payment_gateway_not_implemented', 'Edahabia online payment is not yet available.');
    }

    public function refund(string $transactionRef, int $amount): Payment
    {
        $this->assertConfigured();

        // TODO(payments/edahabia): submit a refund via Edahabia's API.
        throw ApiException::businessRule('payment_gateway_not_implemented', 'Edahabia online payment is not yet available.');
    }

    public function handleWebhook(array $payload, ?string $signature): Payment
    {
        $this->assertConfigured();

        // TODO(payments/edahabia): verify $signature against Edahabia's
        // signing scheme before trusting $payload, then update the matching
        // Payment's status/raw_response from it. Neither is documented yet.
        throw ApiException::businessRule('payment_gateway_not_implemented', 'Edahabia online payment is not yet available.');
    }

    private function assertConfigured(): void
    {
        if (! config('services.edahabia.enabled')
            || ! config('services.edahabia.merchant_id')
            || ! config('services.edahabia.api_key')
            || ! config('services.edahabia.secret')) {
            throw ApiException::businessRule('payment_gateway_not_configured', 'Edahabia payment gateway is not configured.');
        }
    }
}
