<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Models\PaymentWebhookEvent;
use App\Services\NotificationService;
use App\Services\Payments\PaymentGatewayResolver;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * PRD §7.9: gateway webhook receiver. Server-to-server — no Sanctum bearer
 * token is possible here (the gateway calls this directly), so authenticity
 * comes from each provider's own signature verification inside
 * handleWebhook(), not from route middleware.
 */
class PaymentWebhookController extends Controller
{
    public function __construct(
        private readonly PaymentGatewayResolver $gateways,
        private readonly NotificationService $notifications,
    ) {}

    public function __invoke(Request $request, string $gateway): JsonResponse
    {
        try {
            $provider = $this->gateways->resolve($gateway);
        } catch (ApiException $exception) {
            // Unknown/disabled gateway in the URL itself — a routing/config
            // problem, not a transient processing failure, so this is a real
            // 404 rather than the 200-ack-and-log treatment below (which is
            // for gateways that DO exist but failed on this specific event).
            return ApiResponse::error($exception->errorCode, $exception->getMessage(), 404);
        }

        $payloadHash = hash('sha256', $request->getContent());

        $existing = PaymentWebhookEvent::where('gateway', $gateway)
            ->where('payload_hash', $payloadHash)
            ->first();

        if ($existing) {
            // Idempotent replay — acknowledge without reprocessing so a
            // gateway's retry can never double-apply a payment/order update.
            return ApiResponse::success(['status' => 'duplicate'], 200);
        }

        $event = PaymentWebhookEvent::create([
            'gateway' => $gateway,
            'payload_hash' => $payloadHash,
            'payload' => $request->all(),
            'status' => 'received',
        ]);

        try {
            $payment = $provider->handleWebhook($request->all(), $request->header('X-Signature'));

            $event->update(['status' => 'processed', 'transaction_ref' => $payment->transaction_ref]);

            // No real gateway resolves this successfully yet (see
            // CibPaymentProvider/EdahabiaPaymentProvider), so this is
            // unreachable today — wired ahead of time so a future real
            // gateway needs no controller changes, only its own
            // handleWebhook() implementation.
            $order = $payment->order()->with('user')->first();
            if ($order?->user_id && in_array($payment->status, ['success', 'failed'], true)) {
                $eventType = $payment->status === 'success' ? 'payment_successful' : 'payment_failed';
                $this->notifications->notify($order->user, $eventType, [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'payment_id' => $payment->id,
                ]);
            }

            return ApiResponse::success(['status' => 'processed'], 200);
        } catch (ApiException $exception) {
            $event->update(['status' => 'failed', 'error_message' => $exception->getMessage()]);

            // Still acknowledge (200) — a real gateway would otherwise
            // retry-storm us for a provider that's permanently unimplemented
            // until real credentials/docs exist; the failure is logged above
            // for ops to see in the reconciliation view instead.
            return ApiResponse::success(['status' => 'failed', 'reason' => $exception->errorCode], 200);
        }
    }
}
