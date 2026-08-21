<?php

namespace App\Console\Commands;

use App\Mail\AbandonedCartMail;
use App\Models\Cart;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * carts.status already had an 'abandoned' enum value in its very first
 * migration, but nothing ever set it or acted on it — this command is what
 * actually closes that gap. Only registered users are reminded: guest
 * carts have no reliable contact (Cart has no guest email/phone field, only
 * a session_token), so there's no honest way to email them.
 */
class RemindAbandonedCarts extends Command
{
    protected $signature = 'cart:remind-abandoned {--hours=2 : Minimum age in hours since the cart was last touched}';

    protected $description = 'Email registered customers whose cart has items and has been inactive, once per cart.';

    public function handle(): int
    {
        $hours = (int) $this->option('hours');

        $carts = Cart::query()
            ->where('status', 'active')
            ->whereNotNull('user_id')
            ->whereNull('reminder_sent_at')
            ->where('updated_at', '<=', now()->subHours($hours))
            ->whereHas('items')
            ->with('user')
            ->get();

        $sent = 0;

        foreach ($carts as $cart) {
            if (! $cart->user?->email) {
                continue;
            }

            $locale = in_array($cart->user->preferred_language, ['ar', 'fr', 'en'], true)
                ? $cart->user->preferred_language
                : 'ar';
            $cartUrl = rtrim(config('app.frontend_url'), '/')."/{$locale}/cart";

            Mail::to($cart->user->email)->send(new AbandonedCartMail($cart, $cartUrl));
            $cart->update(['reminder_sent_at' => now()]);
            $sent++;
        }

        $this->info("Sent {$sent} abandoned cart reminder(s).");

        return self::SUCCESS;
    }
}
