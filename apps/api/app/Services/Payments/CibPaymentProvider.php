<?php

namespace App\Services\Payments;

use App\Exceptions\ApiException;
use App\Models\Order;
use App\Models\Payment;

/**
 * PRD §7.9/§25 Phase 6: CIB (Carte Interbancaire) online payment gateway.
 * No merchant credentials or CIB API documentation exist yet, so every
 * method here fails clearly instead of guessing at endpoints/signatures —
 * this class only wires the configuration/interface shape so the real
 * integration becomes a drop-in once CIB_* env vars and actual API docs are
 * available (see .env.example and config/services.php).
 */
class CibPaymentProvider implements PaymentProviderInterface
{
    public function initiate(Order $order): Payment
    {
        $this->assertConfigured();

        // TODO(payments/cib): call CIB's real transaction-initiation
        // endpoint once merchant onboarding + API documentation exist.
        throw ApiException::businessRule('payment_gateway_not_implemented', 'CIB online payment is not yet available.');
    }

    public function verify(string $transactionRef): Payment
    {
        $this->assertConfigured();

        // TODO(payments/cib): verify transaction status against CIB's API.
        throw ApiException::businessRule('payment_gateway_not_implemented', 'CIB online payment is not yet available.');
    }

    public function refund(string $transactionRef, int $amount): Payment
    {
        $this->assertConfigured();

        // TODO(payments/cib): submit a refund via CIB's API.
        throw ApiException::businessRule('payment_gateway_not_implemented', 'CIB online payment is not yet available.');
    }

    public function handleWebhook(array $payload, ?string $signature): Payment
    {
        $this->assertConfigured();

        // TODO(payments/cib): verify $signature against CIB's signing scheme
        // before trusting $payload, then update the matching Payment's
        // status/raw_response from it. Neither is documented yet.
        throw ApiException::businessRule('payment_gateway_not_implemented', 'CIB online payment is not yet available.');
    }

    private function assertConfigured(): void
    {
        if (! config('services.cib.enabled')
            || ! config('services.cib.merchant_id')
            || ! config('services.cib.api_key')
            || ! config('services.cib.secret')) {
            throw ApiException::businessRule('payment_gateway_not_configured', 'CIB payment gateway is not configured.');
        }
    }
}
