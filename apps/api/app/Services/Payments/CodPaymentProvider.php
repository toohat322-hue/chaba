<?php

namespace App\Services\Payments;

use App\Exceptions\ApiException;
use App\Models\Order;
use App\Models\Payment;

/**
 * COD is modeled as a "provider" too (PRD §7.9): a no-op initiate, with
 * payment_status transitioning on delivery events (OrderService::updateStatus)
 * instead of a gateway callback. No external call is ever made.
 */
class CodPaymentProvider implements PaymentProviderInterface
{
    public function initiate(Order $order): Payment
    {
        return $order->payments()->create([
            'provider' => 'cod',
            'transaction_ref' => null,
            'status' => 'initiated',
            'amount' => $order->grand_total,
            'currency' => 'DZD',
        ]);
    }

    public function verify(string $transactionRef): Payment
    {
        throw ApiException::businessRule('not_applicable', 'COD payments have no gateway transaction to verify.');
    }

    public function refund(string $transactionRef, int $amount): Payment
    {
        throw ApiException::businessRule('not_applicable', 'COD refunds are a manual ledger entry, not implemented yet.');
    }

    public function handleWebhook(array $payload, ?string $signature): Payment
    {
        throw ApiException::businessRule('not_applicable', 'COD payments have no gateway webhooks.');
    }
}
