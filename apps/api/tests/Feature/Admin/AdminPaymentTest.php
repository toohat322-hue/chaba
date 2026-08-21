<?php

namespace Tests\Feature\Admin;

use App\Models\Address;
use App\Models\Commune;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Wilaya;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesStaffUsers;
use Tests\TestCase;

class AdminPaymentTest extends TestCase
{
    use CreatesStaffUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRbac();
    }

    private function makePayment(string $provider = 'cod', int $amount = 100000): Payment
    {
        $wilaya = Wilaya::create(['code' => '16', 'name_ar' => 'ا', 'name_fr' => 'A', 'name_en' => 'A']);
        $commune = Commune::create(['wilaya_code' => $wilaya->code, 'name_ar' => 'ا', 'name_fr' => 'A', 'name_en' => 'A']);
        $address = Address::create([
            'full_name' => 'Amina Test', 'phone' => '0555222333',
            'wilaya_code' => $wilaya->code, 'commune_id' => $commune->id, 'address_line' => 'Rue 1',
        ]);

        $order = Order::create([
            'order_number' => 'CHB-2026-'.random_int(100000, 999999),
            'guest_name' => 'Amina Test',
            'guest_phone' => '0555222333',
            'address_id' => $address->id,
            'delivery_method' => 'home',
            'payment_method' => $provider,
            'payment_status' => 'pending',
            'order_status' => 'pending',
            'subtotal' => $amount,
            'grand_total' => $amount,
        ]);

        return Payment::create([
            'order_id' => $order->id,
            'provider' => $provider,
            'transaction_ref' => null,
            'status' => 'initiated',
            'amount' => $amount,
            'currency' => 'DZD',
        ]);
    }

    public function test_finance_manager_can_list_payments(): void
    {
        [, $headers] = $this->actingAsRole('Finance Manager');
        $payment = $this->makePayment();

        $response = $this->withHeaders($headers)->getJson('/api/v1/admin/payments');

        $response->assertStatus(200)
            ->assertJsonPath('data.items.0.id', $payment->id)
            ->assertJsonPath('data.items.0.reconciliation_status', 'unreconciled');
    }

    public function test_finance_manager_can_view_a_single_payment(): void
    {
        [, $headers] = $this->actingAsRole('Finance Manager');
        $payment = $this->makePayment();

        $response = $this->withHeaders($headers)->getJson("/api/v1/admin/payments/{$payment->id}");

        $response->assertStatus(200)->assertJsonPath('data.order_number', $payment->order->order_number);
    }

    public function test_finance_manager_can_reconcile_a_cod_payment(): void
    {
        [$user, $headers] = $this->actingAsRole('Finance Manager');
        $payment = $this->makePayment();

        $response = $this->withHeaders($headers)->patchJson("/api/v1/admin/payments/{$payment->id}/reconcile", [
            'reconciliation_status' => 'reconciled',
            'notes' => 'Matched against courier remittance sheet.',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.reconciliation_status', 'reconciled')
            ->assertJsonPath('data.reconciliation_notes', 'Matched against courier remittance sheet.')
            ->assertJsonPath('data.reconciled_by', $user->full_name);

        $this->assertNotNull($payment->fresh()->reconciled_at);
    }

    public function test_marking_a_payment_disputed_records_notes(): void
    {
        [, $headers] = $this->actingAsRole('Finance Manager');
        $payment = $this->makePayment();

        $response = $this->withHeaders($headers)->patchJson("/api/v1/admin/payments/{$payment->id}/reconcile", [
            'reconciliation_status' => 'disputed',
            'notes' => 'Amount collected does not match courier report.',
        ]);

        $response->assertStatus(200)->assertJsonPath('data.reconciliation_status', 'disputed');
    }

    public function test_an_invalid_reconciliation_status_is_rejected(): void
    {
        [, $headers] = $this->actingAsRole('Finance Manager');
        $payment = $this->makePayment();

        $this->withHeaders($headers)
            ->patchJson("/api/v1/admin/payments/{$payment->id}/reconcile", ['reconciliation_status' => 'bogus'])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'invalid_reconciliation_status');
    }

    public function test_a_role_without_payments_permission_is_forbidden(): void
    {
        [, $headers] = $this->actingAsRole('Product Manager');

        $this->withHeaders($headers)->getJson('/api/v1/admin/payments')->assertStatus(403);
    }
}
