<?php

namespace Tests\Feature;

use App\Models\PaymentWebhookEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_for_an_unknown_gateway_returns_404(): void
    {
        $response = $this->postJson('/api/v1/webhooks/payments/totally-made-up', ['foo' => 'bar']);

        $response->assertStatus(404)->assertJsonPath('error.code', 'payment_method_unavailable');
        $this->assertSame(0, PaymentWebhookEvent::count());
    }

    public function test_webhook_for_a_disabled_gateway_returns_404_and_logs_nothing(): void
    {
        config(['services.cib.enabled' => false]);

        $response = $this->postJson('/api/v1/webhooks/payments/cib', ['foo' => 'bar']);

        $response->assertStatus(404);
        $this->assertSame(0, PaymentWebhookEvent::count());
    }

    public function test_webhook_for_cod_is_acknowledged_logged_and_marked_not_applicable(): void
    {
        $response = $this->postJson('/api/v1/webhooks/payments/cod', ['reference' => 'irrelevant-for-cod']);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'failed')
            ->assertJsonPath('data.reason', 'not_applicable');

        $event = PaymentWebhookEvent::first();
        $this->assertNotNull($event);
        $this->assertSame('cod', $event->gateway);
        $this->assertSame('failed', $event->status);
    }

    public function test_webhook_for_an_enabled_but_unconfigured_gateway_is_logged_as_not_configured(): void
    {
        config([
            'services.cib.enabled' => true,
            'services.cib.merchant_id' => null,
            'services.cib.api_key' => null,
            'services.cib.secret' => null,
        ]);

        $response = $this->postJson('/api/v1/webhooks/payments/cib', ['reference' => 'tx-123']);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'failed')
            ->assertJsonPath('data.reason', 'payment_gateway_not_configured');

        $this->assertSame('failed', PaymentWebhookEvent::first()->status);
    }

    public function test_an_identical_duplicate_webhook_payload_is_not_reprocessed(): void
    {
        $payload = ['reference' => 'duplicate-test'];

        $first = $this->postJson('/api/v1/webhooks/payments/cod', $payload);
        $first->assertStatus(200)->assertJsonPath('data.status', 'failed');

        $second = $this->postJson('/api/v1/webhooks/payments/cod', $payload);
        $second->assertStatus(200)->assertJsonPath('data.status', 'duplicate');

        // Only one event row exists for the two identical deliveries.
        $this->assertSame(1, PaymentWebhookEvent::count());
    }

    public function test_two_different_payloads_are_both_logged_separately(): void
    {
        $this->postJson('/api/v1/webhooks/payments/cod', ['reference' => 'first'])->assertStatus(200);
        $this->postJson('/api/v1/webhooks/payments/cod', ['reference' => 'second'])->assertStatus(200);

        $this->assertSame(2, PaymentWebhookEvent::count());
    }
}
