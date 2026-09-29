<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Banner;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BannerUpdateTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Storage::fake('s3');

        $this->admin = Admin::factory()->create();
        $this->admin->makeSuperAdmin();
        $this->actingAs($this->admin, 'admin');
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'link' => 'https://example.com/deal',
            'type' => 'hero',
            'position' => 'home_top',
            'sort_order' => 1,
            'status' => 'active',
        ], $overrides);
    }

    private function bannerWithBothImages(): Banner
    {
        return Banner::query()->create([
            'image' => 'banners/old-desktop.jpg',
            'mobile_image' => 'banners/old-mobile.jpg',
            'link' => 'https://example.com/deal',
            'type' => 'hero',
            'position' => 'home_top',
            'sort_order' => 1,
            'status' => 'active',
        ]);
    }

    public function test_uploading_only_a_desktop_image_leaves_the_mobile_image_untouched(): void
    {
        $banner = $this->bannerWithBothImages();

        $this->put(route('admin.banners.update', $banner), $this->payload([
            'image' => UploadedFile::fake()->image('new-desktop.jpg'),
        ]))->assertRedirect(route('admin.banners.index'));

        $banner->refresh();

        $this->assertNotSame('banners/old-desktop.jpg', $banner->image);
        $this->assertSame('banners/old-mobile.jpg', $banner->mobile_image);
    }

    public function test_uploading_only_a_mobile_image_leaves_the_desktop_image_untouched(): void
    {
        $banner = $this->bannerWithBothImages();

        $this->put(route('admin.banners.update', $banner), $this->payload([
            'mobile_image' => UploadedFile::fake()->image('new-mobile.jpg'),
        ]))->assertRedirect(route('admin.banners.index'));

        $banner->refresh();

        $this->assertSame('banners/old-desktop.jpg', $banner->image);
        $this->assertNotSame('banners/old-mobile.jpg', $banner->mobile_image);
    }

    public function test_saving_without_any_image_fields_keeps_both_existing_images(): void
    {
        $banner = $this->bannerWithBothImages();

        $this->put(route('admin.banners.update', $banner), $this->payload())
            ->assertRedirect(route('admin.banners.index'));

        $banner->refresh();

        $this->assertSame('banners/old-desktop.jpg', $banner->image);
        $this->assertSame('banners/old-mobile.jpg', $banner->mobile_image);
    }

    public function test_the_remove_flag_clears_the_mobile_image(): void
    {
        $banner = $this->bannerWithBothImages();

        $this->put(route('admin.banners.update', $banner), $this->payload([
            'remove_mobile_image' => 1,
        ]))->assertRedirect(route('admin.banners.index'));

        $banner->refresh();

        $this->assertNull($banner->mobile_image);
        $this->assertSame('banners/old-desktop.jpg', $banner->image);
    }

    public function test_a_mobile_image_url_is_stored_without_touching_the_desktop_image(): void
    {
        $banner = $this->bannerWithBothImages();

        $this->put(route('admin.banners.update', $banner), $this->payload([
            'mobile_image_url' => 'https://cdn.example.com/m.jpg',
        ]))->assertRedirect(route('admin.banners.index'));

        $banner->refresh();

        $this->assertSame('https://cdn.example.com/m.jpg', $banner->mobile_image);
        $this->assertSame('banners/old-desktop.jpg', $banner->image);
    }

    public function test_the_banner_list_shows_the_mobile_thumbnail(): void
    {
        $this->bannerWithBothImages();

        $this->get(route('admin.banners.index'))
            ->assertOk()
            ->assertSee('Desktop')
            ->assertSee('Mobile')
            ->assertSee(image_url('banners/old-mobile.jpg'));
    }

    public function test_the_banner_list_flags_banners_that_fall_back_to_desktop(): void
    {
        Banner::query()->create([
            'image' => 'banners/only-desktop.jpg',
            'link' => 'https://example.com/deal',
            'type' => 'hero',
            'position' => 'home_top',
            'sort_order' => 1,
            'status' => 'active',
        ]);

        $this->get(route('admin.banners.index'))
            ->assertOk()
            ->assertSee('Uses desktop');
    }
}
