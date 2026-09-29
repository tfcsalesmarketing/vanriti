<?php

namespace Tests\Feature;

use App\Models\Banner;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BannerMobileImageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
    }

    private function hero(array $overrides = []): Banner
    {
        return Banner::query()->create(array_merge([
            'image' => 'banners/desktop.jpg',
            'mobile_image' => 'banners/mobile.jpg',
            'type' => 'hero',
            'position' => 'home_top',
            'sort_order' => 1,
            'status' => 'active',
        ], $overrides));
    }

    public function test_hero_serves_the_mobile_image_to_phones_and_desktop_to_everything_else(): void
    {
        $banner = $this->hero();

        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertStringContainsString(
            '<source media="(max-width: 767.98px)" srcset="'.image_url('banners/mobile.jpg').'">',
            $html
        );
        $this->assertStringContainsString('src="'.image_url('banners/desktop.jpg').'"', $html);
    }

    public function test_hero_falls_back_to_the_desktop_image_when_no_mobile_image_is_set(): void
    {
        $this->hero(['mobile_image' => null]);

        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertStringNotContainsString('<source media="(max-width: 767.98px)"', $html);
        $this->assertStringContainsString('src="'.image_url('banners/desktop.jpg').'"', $html);
    }

    public function test_hero_keeps_the_banner_link(): void
    {
        $this->hero(['link' => 'https://example.com/deal']);

        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertStringContainsString('href="https://example.com/deal"', $html);
    }

    public function test_mobile_image_url_is_honoured_when_the_path_is_an_absolute_url(): void
    {
        $this->hero(['mobile_image' => 'https://cdn.example.com/m.jpg']);

        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertStringContainsString('srcset="https://cdn.example.com/m.jpg"', $html);
    }
}
