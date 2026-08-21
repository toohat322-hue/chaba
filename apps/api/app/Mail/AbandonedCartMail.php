<?php

namespace App\Mail;

use App\Models\Cart;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AbandonedCartMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Cart $cart, public readonly string $cartUrl) {}

    public function build(): self
    {
        return $this->subject('نسيت شيئًا في سلتك؟ - CHABA')
            ->view('emails.abandoned-cart')
            ->with([
                'cart' => $this->cart->loadMissing('items.variant.product'),
                'cartUrl' => $this->cartUrl,
            ]);
    }
}
