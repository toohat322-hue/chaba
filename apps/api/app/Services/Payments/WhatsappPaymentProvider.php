<?php

namespace App\Services\Payments;

use App\Exceptions\ApiException;
use App\Models\Order;
use App\Models\Payment;

/**
 * WhatsApp orders are modeled as a "provider" too (same shape as
 * CodPaymentProvider): a no-op initiate, real settlement is negotiated in
 * the WhatsApp chat rather than through a gateway callback. No external
 * call is ever made.
 */
class WhatsappPaymentProvider implements PaymentProviderInterface
{
    public function initiate(Order $order): Payment
    {
        return $order->payments()->create([
            'provider' => 'whatsapp',
            'transaction_ref' => null,
            'status' => 'initiated',
            'amount' => $order->grand_total,
            'currency' => 'DZD',
        ]);
    }

    public function verify(string $transactionRef): Payment
    {
        throw ApiException::businessRule('not_applicable', 'WhatsApp orders have no gateway transaction to verify.');
    }

    public function refund(string $transactionRef, int $amount): Payment
    {
        throw ApiException::businessRule('not_applicable', 'WhatsApp order refunds are a manual ledger entry, not implemented yet.');
    }

    public function handleWebhook(array $payload, ?string $signature): Payment
    {
        throw ApiException::businessRule('not_applicable', 'WhatsApp orders have no gateway webhooks.');
    }
}
