<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderConfirmedMail extends Mailable
{
    use Queueable, SerializesModels;

    private const PAYMENT_METHOD_LABELS = [
        'cod' => 'الدفع عند الاستلام',
        'cib' => 'بطاقة CIB',
        'edahabia' => 'بطاقة الذهبية',
        'whatsapp' => 'تأكيد عبر واتساب',
    ];

    public function __construct(public readonly Order $order) {}

    public function build(): self
    {
        $order = $this->order->loadMissing(['items', 'address.wilaya', 'address.commune']);
        $frontendUrl = rtrim(config('app.frontend_url'), '/');

        // Guest orders have no account to log into, so the tracking link
        // must carry both the order number and phone the same way the
        // public track-order page's own form does (OrderTrackController's
        // lookup key) — prefilled so a tap opens straight to the order
        // instead of an empty form asking the customer to retype both.
        $trackingUrl = "{$frontendUrl}/ar/track-order?".http_build_query([
            'order_number' => $order->order_number,
            'phone' => $order->guest_phone,
        ]);

        return $this->subject("تأكيد الطلب {$order->order_number} - CHABA")
            ->view('emails.order-confirmed')
            ->with([
                'order' => $order,
                'logoUrl' => "{$frontendUrl}/brand/logo.png",
                'trackingUrl' => $trackingUrl,
                'paymentMethodLabel' => self::PAYMENT_METHOD_LABELS[$order->payment_method] ?? $order->payment_method,
            ]);
    }
}
