<?php

namespace Tests\Feature\Admin;

use App\Models\FooterColumn;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesStaffUsers;
use Tests\TestCase;

class FooterAdminTest extends TestCase
{
    use CreatesStaffUsers, RefreshDatabase;

    public function test_marketing_manager_can_manage_footer_features(): void
    {
        $this->seedRbac();
        [, $headers] = $this->actingAsRole('Marketing Manager');

        $created = $this->withHeaders($headers)->postJson('/api/v1/admin/footer/features', [
            'icon' => 'truck', 'title_ar' => 'أ', 'title_fr' => 'f', 'title_en' => 'e',
        ])->assertStatus(201)->json('data');

        $this->withHeaders($headers)->patchJson("/api/v1/admin/footer/features/{$created['id']}", ['is_active' => false])
            ->assertStatus(200)->assertJsonPath('data.is_active', false);

        $this->withHeaders($headers)->deleteJson("/api/v1/admin/footer/features/{$created['id']}")->assertStatus(200);
        $this->assertDatabaseMissing('footer_features', ['id' => $created['id']]);
    }

    public function test_footer_feature_rejects_an_unknown_icon_slug(): void
    {
        $this->seedRbac();
        [, $headers] = $this->actingAsRole('Marketing Manager');

        $this->withHeaders($headers)->postJson('/api/v1/admin/footer/features', [
            'icon' => 'not-a-real-icon', 'title_ar' => 'أ', 'title_fr' => 'f', 'title_en' => 'e',
        ])->assertStatus(400);
    }

    public function test_a_role_without_footer_permission_is_forbidden(): void
    {
        $this->seedRbac();
        [, $headers] = $this->actingAsRole('Order Manager');

        $this->withHeaders($headers)->getJson('/api/v1/admin/footer/features')->assertStatus(403);
        $this->withHeaders($headers)->postJson('/api/v1/admin/footer/social-links', ['platform' => 'instagram', 'url' => 'https://instagram.com/x'])
            ->assertStatus(403);
    }

    public function test_columns_and_nested_links_full_lifecycle(): void
    {
        $this->seedRbac();
        [, $headers] = $this->actingAsRole('Marketing Manager');

        $column = $this->withHeaders($headers)->postJson('/api/v1/admin/footer/columns', [
            'title_ar' => 'تصفح', 'title_fr' => 'Parcourir', 'title_en' => 'Browse',
        ])->assertStatus(201)->json('data');

        $link = $this->withHeaders($headers)->postJson("/api/v1/admin/footer/columns/{$column['id']}/links", [
            'label_ar' => 'رابط', 'label_fr' => 'lien', 'label_en' => 'link', 'url' => '/faq',
        ])->assertStatus(201)->json('data');

        $this->withHeaders($headers)->getJson('/api/v1/admin/footer/columns')
            ->assertJsonPath('data.0.links.0.id', $link['id']);

        // Deleting the column cascades to its links (footer_links.footer_column_id
        // has cascadeOnDelete at the DB level).
        $this->withHeaders($headers)->deleteJson("/api/v1/admin/footer/columns/{$column['id']}")->assertStatus(200);
        $this->assertDatabaseMissing('footer_columns', ['id' => $column['id']]);
        $this->assertDatabaseMissing('footer_links', ['id' => $link['id']]);
    }

    public function test_footer_link_url_must_be_an_internal_path(): void
    {
        $this->seedRbac();
        [, $headers] = $this->actingAsRole('Marketing Manager');
        $column = FooterColumn::create(['title_ar' => 'ع', 'title_fr' => 'c', 'title_en' => 'c']);

        $this->withHeaders($headers)->postJson("/api/v1/admin/footer/columns/{$column->id}/links", [
            'label_ar' => 'ع', 'label_fr' => 'l', 'label_en' => 'l', 'url' => 'https://external-site.com',
        ])->assertStatus(400);
    }

    public function test_social_links_full_lifecycle_and_url_validation(): void
    {
        $this->seedRbac();
        [, $headers] = $this->actingAsRole('Marketing Manager');

        $this->withHeaders($headers)->postJson('/api/v1/admin/footer/social-links', [
            'platform' => 'instagram', 'url' => 'not-a-url',
        ])->assertStatus(400);

        $created = $this->withHeaders($headers)->postJson('/api/v1/admin/footer/social-links', [
            'platform' => 'instagram', 'url' => 'https://instagram.com/chaba',
        ])->assertStatus(201)->json('data');

        $this->withHeaders($headers)->patchJson("/api/v1/admin/footer/social-links/{$created['id']}", ['sort_order' => 5])
            ->assertStatus(200)->assertJsonPath('data.sort_order', 5);

        $this->withHeaders($headers)->deleteJson("/api/v1/admin/footer/social-links/{$created['id']}")->assertStatus(200);
    }

    public function test_payment_methods_full_lifecycle(): void
    {
        $this->seedRbac();
        [, $headers] = $this->actingAsRole('Marketing Manager');

        $created = $this->withHeaders($headers)->postJson('/api/v1/admin/footer/payment-methods', [
            'name_ar' => 'الدفع عند الاستلام', 'name_fr' => 'COD', 'name_en' => 'COD', 'icon' => 'cod',
        ])->assertStatus(201)->json('data');

        $this->withHeaders($headers)->patchJson("/api/v1/admin/footer/payment-methods/{$created['id']}", ['icon' => 'visa'])
            ->assertStatus(200)->assertJsonPath('data.icon', 'visa');
    }

    public function test_about_and_whatsapp_fields_are_editable_via_the_settings_endpoint(): void
    {
        $this->seedRbac();
        [, $headers] = $this->actingAsRole('Super Admin');

        $response = $this->withHeaders($headers)->patchJson('/api/v1/admin/settings', [
            'store_name' => 'CHABA',
            'tax_rate_bps' => 0,
            'about_title_ar' => 'من نحن', 'about_title_fr' => 'À propos', 'about_title_en' => 'About',
            'whatsapp_number' => '213555000000', 'whatsapp_active' => true,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.about.title.ar', 'من نحن')
            ->assertJsonPath('data.whatsapp.number', '213555000000');
    }

    public function test_newsletter_subscribers_list_requires_footer_view_permission(): void
    {
        $this->seedRbac();
        [, $forbidden] = $this->actingAsRole('Order Manager');
        $this->withHeaders($forbidden)->getJson('/api/v1/admin/newsletter-subscribers')->assertStatus(403);

        [, $allowed] = $this->actingAsRole('Marketing Manager');
        $this->withHeaders($allowed)->getJson('/api/v1/admin/newsletter-subscribers')
            ->assertStatus(200)
            ->assertJsonStructure(['data' => ['items', 'meta']]);
    }
}
