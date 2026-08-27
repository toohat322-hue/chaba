<?php

namespace Tests\Unit\Seeders;

use App\Models\DeliveryFee;
use App\Models\Wilaya;
use Database\Seeders\DeliveryFeeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryFeeSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_a_zero_fee_row_for_a_wilaya_with_none_yet(): void
    {
        Wilaya::create(['code' => '16', 'name_ar' => 'الجزائر', 'name_fr' => 'Alger', 'name_en' => 'Algiers']);

        (new DeliveryFeeSeeder)->run();

        $this->assertDatabaseHas('delivery_fees', ['wilaya_code' => '16', 'delivery_method' => 'home', 'fee' => 0]);
    }

    /**
     * Regression: this seeder runs on every deploy (ProductionSeeder, via
     * preDeployCommand) — it must never reset a fee the admin already
     * configured back to 0 (previously used updateOrInsert, which did
     * exactly that on every single deploy).
     */
    public function test_it_never_overwrites_an_existing_fee(): void
    {
        Wilaya::create(['code' => '16', 'name_ar' => 'الجزائر', 'name_fr' => 'Alger', 'name_en' => 'Algiers']);
        DeliveryFee::create(['wilaya_code' => '16', 'delivery_method' => 'home', 'fee' => 60000]);

        (new DeliveryFeeSeeder)->run();

        $this->assertDatabaseHas('delivery_fees', ['wilaya_code' => '16', 'delivery_method' => 'home', 'fee' => 60000]);
    }
}
