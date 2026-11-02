<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\InstagramReel;
use App\Models\Product;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstagramReelsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(SettingsSeeder::class);
    }

    private function superAdmin(): Admin
    {
        return Admin::factory()->superAdmin()->create();
    }

    public function test_guest_cannot_access_reels_admin(): void
    {
        $response = $this->get(route('admin.reels.index'));
        $response->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_view_reels_index(): void
    {
        $admin = $this->superAdmin();

        InstagramReel::create([
            'title' => 'Herbal Hair Oil Routine',
            'instagram_url' => 'https://www.instagram.com/reel/C3x9yZ1/',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin, 'admin')->get(route('admin.reels.index'));

        $response->assertOk();
        $response->assertSee('Instagram Reels');
        $response->assertSee('Herbal Hair Oil Routine');
    }

    public function test_admin_can_create_reel_with_product_tag(): void
    {
        $admin = $this->superAdmin();
        $product = Product::factory()->create([
            'name' => 'Vanriti Ayurvedic Amla Powder',
            'status' => 'active',
        ]);

        $payload = [
            'title' => '100% Pure Amla Hair Mask DIY',
            'caption' => 'How to use pure amla powder for shiny thick hair.',
            'instagram_url' => 'https://www.instagram.com/reel/C8aBcDeFg12/',
            'video_url' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerBlazes.mp4',
            'thumbnail_url' => 'https://images.unsplash.com/photo-1540555700478-4be289fbecef',
            'product_id' => $product->id,
            'views_count' => '14.2K',
            'sort_order' => 1,
            'is_active' => '1',
        ];

        $response = $this->actingAs($admin, 'admin')->post(route('admin.reels.store'), $payload);

        $response->assertRedirect(route('admin.reels.index'));
        $this->assertDatabaseHas('instagram_reels', [
            'title' => '100% Pure Amla Hair Mask DIY',
            'product_id' => $product->id,
            'instagram_shortcode' => 'C8aBcDeFg12',
            'is_active' => 1,
        ]);
    }

    public function test_admin_can_toggle_reel_active_status_via_json(): void
    {
        $admin = $this->superAdmin();

        $reel = InstagramReel::create([
            'title' => 'Toggle Reel',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin, 'admin')->postJson(route('admin.reels.toggle', $reel));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'is_active' => false,
        ]);

        $this->assertFalse($reel->fresh()->is_active);
    }

    public function test_admin_can_toggle_reel_active_status_via_form(): void
    {
        $admin = $this->superAdmin();

        $reel = InstagramReel::create([
            'title' => 'Toggle Reel Form',
            'sort_order' => 1,
            'is_active' => false,
        ]);

        $response = $this->actingAs($admin, 'admin')->post(route('admin.reels.toggle', $reel));

        $response->assertRedirect();
        $this->assertTrue($reel->fresh()->is_active);
    }

    public function test_admin_can_update_reel(): void
    {
        $admin = $this->superAdmin();

        $reel = InstagramReel::create([
            'title' => 'Original Title',
            'sort_order' => 5,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin, 'admin')->put(route('admin.reels.update', $reel), [
            'title' => 'Updated Title',
            'sort_order' => 10,
            'is_active' => '0',
        ]);

        $response->assertRedirect(route('admin.reels.index'));
        $this->assertDatabaseHas('instagram_reels', [
            'id' => $reel->id,
            'title' => 'Updated Title',
            'sort_order' => 10,
            'is_active' => 0,
        ]);
    }

    public function test_admin_can_delete_reel(): void
    {
        $admin = $this->superAdmin();

        $reel = InstagramReel::create([
            'title' => 'Delete Me',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin, 'admin')->delete(route('admin.reels.destroy', $reel));

        $response->assertRedirect(route('admin.reels.index'));
        $this->assertDatabaseMissing('instagram_reels', [
            'id' => $reel->id,
        ]);
    }

    public function test_homepage_renders_active_reels_and_omits_inactive_reels(): void
    {
        $activeReel = InstagramReel::create([
            'title' => 'Active Glow Ritual',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $inactiveReel = InstagramReel::create([
            'title' => 'Hidden Draft Reel',
            'sort_order' => 2,
            'is_active' => false,
        ]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('Watch &amp; Shop', false);
        $response->assertSee('Active Glow Ritual');
        $response->assertDontSee('Hidden Draft Reel');
    }
}
