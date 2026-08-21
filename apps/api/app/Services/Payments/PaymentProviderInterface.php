<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\Payment;

/**
 * PRD §7.9: provider-agnostic payment abstraction so CIB/Edahabia (or any
 * future gateway) can be added later by implementing this interface and
 * registering it, without touching checkout/order logic. Only CodProvider
 * exists today — no merchant credentials for CIB/Edahabia exist yet.
 */
interface PaymentProviderInterface
{
    public function initiate(Order $order): Payment;

    public function verify(string $transactionRef): Payment;

    public function refund(string $transactionRef, int $amount): Payment;

    /**
     * Handle an inbound gateway webhook/callback and return the Payment it
     * affected. Called by PaymentWebhookController after idempotency-checking
     * the raw payload — implementations are responsible for verifying
     * `$signature` against the provider's own scheme before trusting `$payload`.
     *
     * @param  array<string, mixed>  $payload
     */
    public function handleWebhook(array $payload, ?string $signature): Payment;
}
