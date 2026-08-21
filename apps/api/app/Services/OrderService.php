<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Mail\OrderConfirmedMail;
use App\Mail\OrderStatusUpdatedMail;
use App\Models\Address;
use App\Models\Cart;
use App\Models\CouponUsage;
use App\Models\DeliveryFee;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\StoreSetting;
use App\Models\User;
use App\Services\Payments\PaymentGatewayResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * PRD §7.7/§7.10: cart -> order, and the order status lifecycle. Mirrors
 * CartService's transaction-and-lock discipline throughout.
 */
class OrderService
{
    private const FORWARD_TRANSITIONS = [
        'pending' => ['confirmed', 'cancelled'],
        'confirmed' => ['processing', 'cancelled'],
        'processing' => ['ready_to_ship', 'cancelled'],
        'ready_to_ship' => ['shipped', 'cancelled'],
        'shipped' => ['out_for_delivery', 'returned'],
        'out_for_delivery' => ['delivered', 'returned'],
        'delivered' => ['returned', 'refunded'],
    ];

    public function __construct(
        private readonly PaymentGatewayResolver $paymentGateways,
        private readonly CouponService $coupons,
        private readonly NotificationService $notifications,
    ) {}

    public function checkout(Cart $cart, array $data, ?User $user): array
    {
        return DB::transaction(function () use ($cart, $data, $user) {
            // Re-fetch under lock: a concurrent double-submit racing this
            // same cart blocks here until the first request's transaction
            // commits (and flips status to 'converted' below), then this
            // check catches it cleanly instead of creating a second order.
            $cart = Cart::query()->lockForUpdate()->findOrFail($cart->id);
            if ($cart->status !== 'active') {
                throw ApiException::conflict('cart_already_converted', 'This cart has already been checked out.');
            }

            $items = $cart->items()->with('variant.product')->get();

            if ($items->isEmpty()) {
                throw ApiException::businessRule('cart_empty', 'Your cart is empty.');
            }

            $address = $this->resolveAddress($data, $user);
            $deliveryMethod = $data['delivery_method'];

            $fee = DeliveryFee::where('wilaya_code', $address->wilaya_code)
                ->where('delivery_method', $deliveryMethod)
                ->first();
            $deliveryFee = $fee->fee ?? 0;

            $subtotal = $items->sum(fn ($item) => $item->price_snapshot * $item->quantity);

            $coupon = null;
            $discountTotal = 0;

            if ($cart->coupon_code) {
                // Re-validated under lock — the cart-side apply was only a
                // preview; this is the authoritative check that closes the
                // race on a coupon's usage limit (same discipline as the
                // stock re-check a few lines below).
                $coupon = $this->coupons->findValid($cart->coupon_code, $subtotal, $user, lock: true);
                $discountTotal = $this->coupons->calculateDiscount($coupon, $subtotal);

                if ($coupon->type === 'free_shipping') {
                    $deliveryFee = 0;
                }
            }

            // Admin Settings' tax rate (basis points, default 0 — unset means
            // unchanged behavior from before this was configurable) applied
            // to the post-discount subtotal, not delivery.
            $taxRateBps = StoreSetting::current()->tax_rate_bps;
            $taxTotal = intdiv(($subtotal - $discountTotal) * $taxRateBps, 10000);

            $order = Order::create([
                'order_number' => $this->generateOrderNumber(),
                'user_id' => $user?->id,
                'guest_name' => $data['customer_name'],
                'guest_phone' => $data['customer_phone'],
                'guest_email' => $data['customer_email'] ?? null,
                'address_id' => $address->id,
                'delivery_method' => $deliveryMethod,
                'payment_method' => $data['payment_method'],
                'payment_status' => 'pending',
                'order_status' => 'pending',
                'subtotal' => $subtotal,
                'discount_total' => $discountTotal,
                'delivery_fee' => $deliveryFee,
                'tax_total' => $taxTotal,
                'grand_total' => $subtotal - $discountTotal + $deliveryFee + $taxTotal,
                'coupon_id' => $coupon?->id,
                'notes' => $data['notes'] ?? null,
            ]);

            if ($coupon) {
                CouponUsage::create([
                    'coupon_id' => $coupon->id,
                    'user_id' => $user?->id,
                    'order_id' => $order->id,
                    'used_at' => now(),
                ]);
            }

            $locale = in_array($data['locale'] ?? null, ['ar', 'fr', 'en'], true) ? $data['locale'] : 'ar';
            $nameColumn = "name_{$locale}";

            foreach ($items as $item) {
                // Re-validated and committed under lock in the same pass —
                // the cart's reservation could be stale (e.g. an admin
                // manually corrected stock down after this cart reserved it).
                $inventory = Inventory::where('variant_id', $item->variant_id)->lockForUpdate()->first();

                // The reservation guaranteed enough was set aside *at add-to-cart
                // time* — what matters now is whether real stock_quantity can
                // still actually cover the commit (it can't if it was corrected
                // downward after the reservation was made).
                if (! $inventory || $inventory->stock_quantity < $item->quantity) {
                    throw ApiException::conflict('insufficient_stock', 'Not enough stock available for one or more items.');
                }

                $order->items()->create([
                    'variant_id' => $item->variant_id,
                    'product_name_snapshot' => $item->variant->product->{$nameColumn},
                    'sku_snapshot' => $item->variant->sku,
                    // Frozen at purchase time, same as product_name_snapshot/
                    // sku_snapshot — a later admin edit to the variant's size
                    // must never alter what an already-placed order shows.
                    'size_snapshot' => $item->variant->formattedSize(),
                    'unit_price' => $item->price_snapshot,
                    'quantity' => $item->quantity,
                    'line_total' => $item->price_snapshot * $item->quantity,
                ]);

                // Reservation becomes a committed sale: stock actually
                // leaves, and the reservation that held it is released —
                // never a double-decrement of the same units.
                $inventory->decrement('stock_quantity', $item->quantity);
                $inventory->decrement('reserved_quantity', $item->quantity);
            }

            $order->statusHistory()->create([
                'status' => 'pending',
                'note' => 'Order placed.',
                'actor_id' => null,
                'created_at' => now(),
            ]);

            $this->paymentGateways->resolve($data['payment_method'])->initiate($order);

            $cart->update(['status' => 'converted']);

            $this->notifyConfirmation($order);

            // In-app notification (PRD §25 Phase 6) — guests have no account
            // to attach one to; notifyConfirmation() above (log + email) is
            // their only confirmation channel today.
            if ($user) {
                $this->notifications->notify($user, 'order_created', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                ]);
            }

            $freshOrder = $order->fresh(['items', 'address.wilaya', 'address.commune', 'statusHistory', 'payments', 'coupon']);

            $whatsapp = null;
            if ($data['payment_method'] === 'whatsapp') {
                // Availability (whatsapp_orders_enabled / whatsapp_active /
                // whatsapp_number all set) was already enforced in
                // CheckoutRequest::availablePaymentMethods() — reaching this
                // branch means it's safe to build the link.
                $number = StoreSetting::current()->whatsapp_number;
                $digits = preg_replace('/\D/', '', $number);
                $message = app(WhatsAppOrderMessageBuilder::class)->build($freshOrder, $locale);
                $whatsapp = [
                    'phone' => $digits,
                    'message' => $message,
                    // rawurlencode (RFC 3986) — wa.me needs %20 for spaces,
                    // not the '+' that urlencode() would produce.
                    'url' => "https://wa.me/{$digits}?text=".rawurlencode($message),
                ];
            }

            return ['order' => $freshOrder, 'whatsapp' => $whatsapp];
        });
    }

    public function updateStatus(Order $order, string $newStatus, ?string $note, User $actor): Order
    {
        return DB::transaction(function () use ($order, $newStatus, $note, $actor) {
            $allowed = self::FORWARD_TRANSITIONS[$order->order_status] ?? [];

            if (! in_array($newStatus, $allowed, true)) {
                throw ApiException::businessRule(
                    'invalid_status_transition',
                    "Cannot move an order from {$order->order_status} to {$newStatus}.",
                );
            }

            if (in_array($newStatus, ['cancelled', 'returned'], true)) {
                // Unconditional +N restock, same as CartService::removeItem —
                // no read-then-decide step, so a plain UPDATE is already atomic.
                foreach ($order->items as $item) {
                    Inventory::where('variant_id', $item->variant_id)->increment('stock_quantity', $item->quantity);
                }

                if ($order->payment_method === 'cod') {
                    $order->payment_status = 'cod_pending_collection';
                }
            }

            if ($newStatus === 'delivered' && $order->payment_method === 'cod') {
                $order->payment_status = 'cod_collected';
            }

            $order->order_status = $newStatus;
            $order->save();

            $order->statusHistory()->create([
                'status' => $newStatus,
                'note' => $note,
                'actor_id' => $actor->id,
                'created_at' => now(),
            ]);

            if ($order->user_id && in_array($newStatus, ['shipped', 'delivered', 'cancelled'], true)) {
                $this->notifications->notify($order->user, "order_{$newStatus}", [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                ]);
            }

            if (in_array($newStatus, ['shipped', 'delivered', 'cancelled'], true)) {
                $this->notifyStatusChange($order);
            }

            return $order->fresh(['items', 'address.wilaya', 'address.commune', 'statusHistory', 'payments', 'coupon']);
        });
    }

    private function resolveAddress(array $data, ?User $user): Address
    {
        if (! empty($data['address_id'])) {
            $address = Address::find($data['address_id']);

            if (! $address || ! $user || $address->user_id !== $user->id) {
                throw new ApiException('not_found', 'Address not found.', 404);
            }

            return $address;
        }

        $addressData = $data['address'];

        return Address::create([
            'user_id' => $user?->id,
            'full_name' => $addressData['full_name'],
            'phone' => $addressData['phone'],
            'wilaya_code' => $addressData['wilaya_code'],
            'commune_id' => $addressData['commune_id'],
            'address_line' => $addressData['address_line'],
            'landmark' => $addressData['landmark'] ?? null,
            'postal_code' => $addressData['postal_code'] ?? null,
            'is_default' => false,
        ]);
    }

    private function generateOrderNumber(): string
    {
        $next = DB::selectOne("select nextval('order_number_seq') as n")->n;

        return sprintf('CHB-%d-%06d', now()->year, $next);
    }

    /**
     * SMS confirmation goes through SmsService (see OtpService) once order
     * confirmation SMS is wired up there too; email is real today via
     * OrderConfirmedMail.
     */
    private function notifyConfirmation(Order $order): void
    {
        Log::info("Order confirmed: {$order->order_number} for {$order->guest_phone}");

        $recipient = $this->notificationEmail($order);

        if ($recipient) {
            Mail::to($recipient)->send(new OrderConfirmedMail($order));
        }
    }

    private function notifyStatusChange(Order $order): void
    {
        $recipient = $this->notificationEmail($order);

        if ($recipient) {
            Mail::to($recipient)->send(new OrderStatusUpdatedMail($order));
        }
    }

    /**
     * Registered users may not have filled guest_email at checkout (it's a
     * guest-only field) — their account email is the real address to use.
     * Falls back to the SMS dev inbox only so order emails stay visible in
     * Mailhog during local development when neither is set.
     */
    private function notificationEmail(Order $order): ?string
    {
        return $order->user?->email ?? $order->guest_email ?? config('services.sms.dev_inbox');
    }
}
