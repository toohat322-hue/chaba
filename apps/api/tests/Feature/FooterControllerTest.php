<?php

namespace Tests\Feature;

use App\Models\FooterColumn;
use App\Models\FooterFeature;
use App\Models\FooterPaymentMethod;
use App\Models\FooterSocialLink;
use App\Models\StoreSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FooterControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_the_about_and_whatsapp_settings(): void
    {
        StoreSetting::current()->update([
            'about_title_ar' => 'من نحن', 'about_title_fr' => 'À propos', 'about_title_en' => 'About',
            'whatsapp_number' => '213555000000', 'whatsapp_active' => true,
        ]);

        $this->getJson('/api/v1/footer')
            ->assertStatus(200)
            ->assertJsonPath('data.settings.about.title.ar', 'من نحن')
            ->assertJsonPath('data.settings.whatsapp.number', '213555000000');
    }

    public function test_whatsapp_number_is_hidden_when_both_the_fab_and_ordering_are_inactive(): void
    {
        StoreSetting::current()->update([
            'whatsapp_number' => '213555000000', 'whatsapp_active' => false, 'whatsapp_orders_enabled' => false,
        ]);

        $this->getJson('/api/v1/footer')->assertJsonPath('data.settings.whatsapp.number', null);
    }

    public function test_whatsapp_number_stays_visible_for_ordering_even_when_the_fab_is_inactive(): void
    {
        StoreSetting::current()->update([
            'whatsapp_number' => '213555000000', 'whatsapp_active' => false, 'whatsapp_orders_enabled' => true,
        ]);

        $this->getJson('/api/v1/footer')
            ->assertJsonPath('data.settings.whatsapp.number', '213555000000')
            ->assertJsonPath('data.settings.whatsapp.orders_enabled', true);
    }

    public function test_only_active_features_are_returned_in_sort_order(): void
    {
        FooterFeature::create(['icon' => 'truck', 'title_ar' => 'ب', 'title_fr' => 'b', 'title_en' => 'b', 'sort_order' => 2, 'is_active' => true]);
        FooterFeature::create(['icon' => 'shield', 'title_ar' => 'أ', 'title_fr' => 'a', 'title_en' => 'a', 'sort_order' => 1, 'is_active' => true]);
        FooterFeature::create(['icon' => 'lock', 'title_ar' => 'مخفي', 'title_fr' => 'hidden', 'title_en' => 'hidden', 'sort_order' => 0, 'is_active' => false]);

        $response = $this->getJson('/api/v1/footer');

        $response->assertJsonCount(2, 'data.features')
            ->assertJsonPath('data.features.0.title.en', 'a')
            ->assertJsonPath('data.features.1.title.en', 'b');
    }

    public function test_inactive_social_links_are_excluded(): void
    {
        FooterSocialLink::create(['platform' => 'instagram', 'url' => 'https://instagram.com/chaba', 'is_active' => true]);
        FooterSocialLink::create(['platform' => 'facebook', 'url' => 'https://facebook.com/chaba', 'is_active' => false]);

        $response = $this->getJson('/api/v1/footer');

        $response->assertJsonCount(1, 'data.socialLinks')
            ->assertJsonPath('data.socialLinks.0.platform', 'instagram');
    }

    public function test_columns_only_include_their_active_links(): void
    {
        $column = FooterColumn::create(['title_ar' => 'ع', 'title_fr' => 'c', 'title_en' => 'c', 'is_active' => true]);
        $column->links()->create(['label_ar' => 'ظاهر', 'label_fr' => 'visible', 'label_en' => 'visible', 'url' => '/faq', 'is_active' => true]);
        $column->links()->create(['label_ar' => 'مخفي', 'label_fr' => 'hidden', 'label_en' => 'hidden', 'url' => '/x', 'is_active' => false]);

        $response = $this->getJson('/api/v1/footer');

        $response->assertJsonCount(1, 'data.columns.0.links')
            ->assertJsonPath('data.columns.0.links.0.label.en', 'visible');
    }

    public function test_payment_methods_reflect_is_active(): void
    {
        FooterPaymentMethod::create(['name_ar' => 'ع', 'name_fr' => 'p', 'name_en' => 'p', 'icon' => 'cod', 'is_active' => true]);
        FooterPaymentMethod::create(['name_ar' => 'م', 'name_fr' => 'm', 'name_en' => 'm', 'icon' => 'cib', 'is_active' => false]);

        $this->getJson('/api/v1/footer')->assertJsonCount(1, 'data.paymentMethods');
    }
}
