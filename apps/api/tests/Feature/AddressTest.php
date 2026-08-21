<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Commune;
use App\Models\User;
use App\Models\Wilaya;
use App\Services\TokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AddressTest extends TestCase
{
    use RefreshDatabase;

    private function makeCommune(): Commune
    {
        $wilaya = Wilaya::create(['code' => '16', 'name_ar' => 'الجزائر', 'name_fr' => 'Alger', 'name_en' => 'Algiers']);

        return Commune::create([
            'wilaya_code' => $wilaya->code,
            'name_ar' => 'الجزائر الوسطى', 'name_fr' => 'Alger Centre', 'name_en' => 'Algiers Centre',
        ]);
    }

    /** @return array{0: User, 1: array<string, string>} */
    private function makeAuthedCustomer(): array
    {
        $user = User::create([
            'full_name' => 'Amina Test',
            'phone' => '+213555222333',
            'password_hash' => bcrypt('password123'),
            'status' => 'active',
        ]);
        $pair = app(TokenService::class)->issuePair($user);

        return [$user, ['Authorization' => "Bearer {$pair['access_token']}"]];
    }

    public function test_a_customer_can_create_list_update_and_delete_their_own_address(): void
    {
        $commune = $this->makeCommune();
        [, $headers] = $this->makeAuthedCustomer();

        $create = $this->withHeaders($headers)->postJson('/api/v1/addresses', [
            'full_name' => 'Amina Test',
            'phone' => '0555222333',
            'wilaya_code' => $commune->wilaya_code,
            'commune_id' => $commune->id,
            'address_line' => 'Rue des Fleurs 12',
        ]);
        $create->assertStatus(201)->assertJsonPath('data.address_line', 'Rue des Fleurs 12');
        $addressId = $create->json('data.id');

        $this->withHeaders($headers)->getJson('/api/v1/addresses')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');

        $this->withHeaders($headers)->patchJson("/api/v1/addresses/{$addressId}", ['address_line' => 'Rue Nouvelle 5'])
            ->assertStatus(200)
            ->assertJsonPath('data.address_line', 'Rue Nouvelle 5');

        $this->withHeaders($headers)->deleteJson("/api/v1/addresses/{$addressId}")->assertStatus(200);
        $this->withHeaders($headers)->getJson('/api/v1/addresses')->assertJsonCount(0, 'data');
    }

    public function test_a_customer_cannot_view_or_modify_another_customers_address(): void
    {
        $commune = $this->makeCommune();

        $owner = User::create([
            'full_name' => 'Owner', 'phone' => '+213555111111',
            'password_hash' => bcrypt('password123'), 'status' => 'active',
        ]);
        $address = Address::create([
            'user_id' => $owner->id,
            'full_name' => 'Owner', 'phone' => '+213555111111',
            'wilaya_code' => $commune->wilaya_code, 'commune_id' => $commune->id,
            'address_line' => 'Owner street',
        ]);

        [, $strangerHeaders] = $this->makeAuthedCustomer();

        $this->withHeaders($strangerHeaders)->patchJson("/api/v1/addresses/{$address->id}", ['address_line' => 'Hijacked'])
            ->assertStatus(404);

        $this->withHeaders($strangerHeaders)->deleteJson("/api/v1/addresses/{$address->id}")
            ->assertStatus(404);
    }

    public function test_address_endpoints_require_authentication(): void
    {
        $this->getJson('/api/v1/addresses')->assertStatus(401);
    }

    public function test_the_first_address_is_automatically_the_default(): void
    {
        $commune = $this->makeCommune();
        [, $headers] = $this->makeAuthedCustomer();

        $response = $this->withHeaders($headers)->postJson('/api/v1/addresses', [
            'full_name' => 'Amina Test', 'phone' => '0555222333',
            'wilaya_code' => $commune->wilaya_code, 'commune_id' => $commune->id,
            'address_line' => 'Rue des Fleurs 12',
        ]);

        $response->assertStatus(201)->assertJsonPath('data.is_default', true);
    }

    public function test_marking_a_new_address_default_unsets_the_previous_default(): void
    {
        $commune = $this->makeCommune();
        [, $headers] = $this->makeAuthedCustomer();

        $first = $this->withHeaders($headers)->postJson('/api/v1/addresses', [
            'full_name' => 'Amina Test', 'phone' => '0555222333',
            'wilaya_code' => $commune->wilaya_code, 'commune_id' => $commune->id,
            'address_line' => 'First street',
        ])->json('data');

        $second = $this->withHeaders($headers)->postJson('/api/v1/addresses', [
            'full_name' => 'Amina Test', 'phone' => '0555222333',
            'wilaya_code' => $commune->wilaya_code, 'commune_id' => $commune->id,
            'address_line' => 'Second street', 'is_default' => true,
        ])->json('data');

        $this->assertTrue($second['is_default']);

        $list = $this->withHeaders($headers)->getJson('/api/v1/addresses')->json('data');
        $defaults = array_filter($list, fn ($address) => $address['is_default']);
        $this->assertCount(1, $defaults);
        $this->assertSame($second['id'], array_values($defaults)[0]['id']);
        $this->assertNotSame($first['id'], array_values($defaults)[0]['id']);
    }

    public function test_deleting_the_default_address_promotes_another_one(): void
    {
        $commune = $this->makeCommune();
        [, $headers] = $this->makeAuthedCustomer();

        $first = $this->withHeaders($headers)->postJson('/api/v1/addresses', [
            'full_name' => 'Amina Test', 'phone' => '0555222333',
            'wilaya_code' => $commune->wilaya_code, 'commune_id' => $commune->id,
            'address_line' => 'First street',
        ])->json('data');

        $second = $this->withHeaders($headers)->postJson('/api/v1/addresses', [
            'full_name' => 'Amina Test', 'phone' => '0555222333',
            'wilaya_code' => $commune->wilaya_code, 'commune_id' => $commune->id,
            'address_line' => 'Second street',
        ])->json('data');

        $this->assertTrue($first['is_default']);
        $this->assertFalse($second['is_default']);

        $this->withHeaders($headers)->deleteJson("/api/v1/addresses/{$first['id']}")->assertStatus(200);

        $remaining = $this->withHeaders($headers)->getJson('/api/v1/addresses')->json('data');
        $this->assertCount(1, $remaining);
        $this->assertTrue($remaining[0]['is_default']);
        $this->assertSame($second['id'], $remaining[0]['id']);
    }
}
