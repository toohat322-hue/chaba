<?php

namespace Tests\Feature;

use App\Mail\NewsletterSubscriptionConfirmedMail;
use App\Models\NewsletterSubscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NewsletterControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_valid_email_subscribes_successfully(): void
    {
        $response = $this->postJson('/api/v1/newsletter/subscribe', ['email' => 'amina@example.com', 'locale' => 'ar']);

        $response->assertStatus(201)->assertJsonPath('data.email', 'amina@example.com');
        $this->assertDatabaseHas('newsletter_subscribers', ['email' => 'amina@example.com', 'locale' => 'ar']);
    }

    public function test_subscribing_sends_a_confirmation_email_with_a_unique_unsubscribe_token(): void
    {
        Mail::fake();

        $this->postJson('/api/v1/newsletter/subscribe', ['email' => 'amina@example.com', 'locale' => 'fr'])
            ->assertStatus(201);

        $subscriber = NewsletterSubscriber::where('email', 'amina@example.com')->firstOrFail();
        $this->assertNotNull($subscriber->unsubscribe_token);

        Mail::assertSent(NewsletterSubscriptionConfirmedMail::class, function (NewsletterSubscriptionConfirmedMail $mail) use ($subscriber) {
            return $mail->hasTo('amina@example.com') && $mail->subscriber->id === $subscriber->id;
        });
    }

    public function test_a_valid_unsubscribe_token_removes_the_subscriber(): void
    {
        Mail::fake();
        $this->postJson('/api/v1/newsletter/subscribe', ['email' => 'amina@example.com'])->assertStatus(201);
        $subscriber = NewsletterSubscriber::where('email', 'amina@example.com')->firstOrFail();

        $response = $this->deleteJson("/api/v1/newsletter/unsubscribe/{$subscriber->unsubscribe_token}");

        $response->assertStatus(200)->assertJsonPath('data.unsubscribed', true);
        $this->assertDatabaseMissing('newsletter_subscribers', ['id' => $subscriber->id]);
    }

    public function test_an_invalid_unsubscribe_token_is_rejected(): void
    {
        $this->deleteJson('/api/v1/newsletter/unsubscribe/00000000-0000-0000-0000-000000000000')
            ->assertStatus(404);
    }

    public function test_the_same_unsubscribe_token_cannot_be_used_twice(): void
    {
        Mail::fake();
        $this->postJson('/api/v1/newsletter/subscribe', ['email' => 'amina@example.com'])->assertStatus(201);
        $subscriber = NewsletterSubscriber::where('email', 'amina@example.com')->firstOrFail();

        $this->deleteJson("/api/v1/newsletter/unsubscribe/{$subscriber->unsubscribe_token}")->assertStatus(200);
        $this->deleteJson("/api/v1/newsletter/unsubscribe/{$subscriber->unsubscribe_token}")->assertStatus(404);
    }

    public function test_an_invalid_email_is_rejected(): void
    {
        $this->postJson('/api/v1/newsletter/subscribe', ['email' => 'not-an-email'])
            ->assertStatus(400)
            ->assertJsonPath('error.code', 'validation_error');
    }

    public function test_a_duplicate_email_is_rejected_case_insensitively(): void
    {
        NewsletterSubscriber::create(['email' => 'amina@example.com', 'subscribed_at' => now()]);

        $this->postJson('/api/v1/newsletter/subscribe', ['email' => 'Amina@Example.com'])
            ->assertStatus(400)
            ->assertJsonPath('error.code', 'validation_error');

        $this->assertSame(1, NewsletterSubscriber::count());
    }

    public function test_subscribing_is_rate_limited(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/v1/newsletter/subscribe', ['email' => "user{$i}@example.com"])
                ->assertStatus(201);
        }

        $this->postJson('/api/v1/newsletter/subscribe', ['email' => 'oneMore@example.com'])
            ->assertStatus(429);
    }
}
