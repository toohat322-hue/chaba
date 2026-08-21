<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderStatusUpdatedMail extends Mailable
{
    use Queueable, SerializesModels;

    private const STATUS_LABELS = [
        'confirmed' => 'مؤكد',
        'processing' => 'قيد التحضير',
        'shipped' => 'تم الشحن',
        'delivered' => 'تم التوصيل',
        'cancelled' => 'ملغى',
        'failed' => 'فشل',
    ];

    public function __construct(public readonly Order $order) {}

    public function build(): self
    {
        $label = self::STATUS_LABELS[$this->order->order_status] ?? $this->order->order_status;

        return $this->subject("تحديث حالة الطلب {$this->order->order_number}: {$label} - CHABA")
            ->view('emails.order-status-updated')
            ->with(['order' => $this->order, 'statusLabel' => $label]);
    }
}
