<?php

namespace Tests\Feature;

use App\Mail\AbandonedCartMail;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class RemindAbandonedCartsTest extends TestCase
{
    use RefreshDatabase;

    private function makeVariant(): ProductVariant
    {
        $category = Category::create([
            'name_ar' => 'فئة', 'name_fr' => 'Cat', 'name_en' => 'Cat',
            'slug' => 'cat-'.Str::random(8), 'is_active' => true,
        ]);
        $product = Product::create([
            'category_id' => $category->id,
            'name_ar' => 'منتج', 'name_fr' => 'Produit', 'name_en' => 'Product',
            'slug' => 'product-'.Str::random(8), 'base_price' => 100000, 'status' => 'active',
        ]);
        $variant = ProductVariant::create(['product_id' => $product->id, 'sku' => 'SKU-'.Str::random(8)]);
        Inventory::create(['variant_id' => $variant->id, 'stock_quantity' => 10, 'reserved_quantity' => 0, 'low_stock_threshold' => 5]);

        return $variant;
    }

    private function makeStaleCart(?string $email, string $status = 'active', ?string $reminderSentAt = null): Cart
    {
        $user = User::create([
            'full_name' => 'Amina Test',
            'email' => $email,
            'phone' => '+2135'.random_int(10000000, 99999999),
            'password_hash' => bcrypt('password123'),
            'status' => 'active',
        ]);

        $cart = Cart::create(['user_id' => $user->id, 'status' => $status, 'reminder_sent_at' => $reminderSentAt]);
        CartItem::create(['cart_id' => $cart->id, 'variant_id' => $this->makeVariant()->id, 'quantity' => 2, 'price_snapshot' => 100000]);
        // A plain Eloquent ->update() re-touches updated_at to "now" (the
        // model's own $timestamps=true behavior) — a query builder update
        // bypasses that so the backdated value actually sticks.
        Cart::where('id', $cart->id)->update(['updated_at' => now()->subHours(5)]);

        return $cart->fresh();
    }

    public function test_a_stale_cart_with_an_email_gets_a_reminder_and_is_marked_sent(): void
    {
        Mail::fake();

        $cart = $this->makeStaleCart('amina@example.com');

        $this->artisan('cart:remind-abandoned')->assertExitCode(0);

        Mail::assertSent(AbandonedCartMail::class, fn (AbandonedCartMail $mail) => $mail->hasTo('amina@example.com'));
        $this->assertNotNull($cart->fresh()->reminder_sent_at);
    }

    public function test_a_recently_touched_cart_is_not_reminded(): void
    {
        Mail::fake();

        $cart = $this->makeStaleCart('amina@example.com');
        // update(['updated_at' => ...]) is silently dropped — updated_at
        // isn't mass-assignable (not in Cart::$fillable). Direct attribute
        // assignment bypasses that.
        $cart->updated_at = now();
        $cart->save();

        $this->artisan('cart:remind-abandoned');

        Mail::assertNothingSent();
    }

    public function test_a_cart_without_a_user_email_is_skipped_without_erroring(): void
    {
        Mail::fake();

        $this->makeStaleCart(null);

        $this->artisan('cart:remind-abandoned')->assertExitCode(0);

        Mail::assertNothingSent();
    }

    public function test_a_cart_already_reminded_is_not_reminded_twice(): void
    {
        Mail::fake();

        $this->makeStaleCart('amina@example.com', reminderSentAt: (string) now()->subHour());

        $this->artisan('cart:remind-abandoned');

        Mail::assertNothingSent();
    }

    public function test_a_converted_cart_is_not_reminded(): void
    {
        Mail::fake();

        $this->makeStaleCart('amina@example.com', status: 'converted');

        $this->artisan('cart:remind-abandoned');

        Mail::assertNothingSent();
    }
}
