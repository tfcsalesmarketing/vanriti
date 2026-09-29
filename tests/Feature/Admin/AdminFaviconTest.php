<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Setting;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminFaviconTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
    }

    private function superAdmin(): Admin
    {
        $admin = Admin::factory()->create();
        $admin->makeSuperAdmin();

        return $admin;
    }

    public function test_the_admin_panel_serves_the_brand_favicon(): void
    {
        $admin = $this->superAdmin();

        $html = $this->actingAs($admin, 'admin')
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('<link rel="icon" href="'.brand_favicon_url().'" sizes="any">', $html);
        $this->assertStringContainsString('<link rel="apple-touch-icon" href="'.brand_favicon_url().'">', $html);
    }

    public function test_the_admin_favicon_is_not_the_hardcoded_fallback_when_a_logo_exists(): void
    {
        $admin = $this->superAdmin();

        $html = $this->actingAs($admin, 'admin')
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->getContent();

        // public/images/logo.webp ships with the app, so the brand logo wins.
        $this->assertStringNotContainsString('<link rel="icon" href="'.asset('favicon.ico').'"', $html);
    }

    public function test_the_admin_error_pages_also_carry_the_brand_favicon(): void
    {
        $html = view('admin.errors.404')->render();

        $this->assertStringContainsString('<link rel="icon" href="'.brand_favicon_url().'" sizes="any">', $html);
        $this->assertStringContainsString('<link rel="apple-touch-icon" href="'.brand_favicon_url().'">', $html);
    }

    public function test_the_brand_favicon_prefers_the_logo_shipped_in_public_images(): void
    {
        // No store_logo setting is seeded, so the bundled logo wins over favicon.ico.
        $this->assertSame('images/logo.webp', brand_logo_path());
        $this->assertSame(asset('images/logo.webp'), brand_favicon_url());
    }

    public function test_a_configured_store_logo_setting_wins_for_both_admin_and_storefront(): void
    {
        Setting::query()->create([
            'key' => 'store_logo',
            'value' => 'https://cdn.example.com/brand.svg',
        ]);

        $this->assertSame('https://cdn.example.com/brand.svg', brand_logo_path());
        $this->assertSame('https://cdn.example.com/brand.svg', brand_favicon_url());

        $admin = $this->superAdmin();
        $expected = '<link rel="icon" href="https://cdn.example.com/brand.svg" sizes="any">';

        $this->actingAs($admin, 'admin')
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee($expected, false);

        $this->get(route('home'))->assertOk()->assertSee($expected, false);
    }

    public function test_the_storefront_and_admin_share_the_same_favicon_url(): void
    {
        $storefront = $this->get(route('home'))->assertOk()->getContent();

        $this->assertStringContainsString('<link rel="icon" href="'.brand_favicon_url().'" sizes="any">', $storefront);
    }
}
