<?php

namespace Tests\Feature;

use App\Models\Banner;
use App\Models\Product;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BannerSectionBannerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
    }

    private function promo(string $position, array $overrides = []): Banner
    {
        return Banner::query()->create(array_merge([
            'title' => 'Promo for '.$position,
            'image' => 'banners/promo.jpg',
            'link' => 'https://example.com/shop',
            'type' => 'promotional',
            'position' => $position,
            'sort_order' => 1,
            'status' => 'active',
        ], $overrides));
    }

    private function html(): string
    {
        return $this->get(route('home'))->assertOk()->getContent();
    }

    public function test_a_banner_renders_in_the_slot_it_is_assigned_to(): void
    {
        $this->promo('after_bestsellers', ['image' => 'banners/bestsellers.jpg']);

        $html = $this->html();

        $this->assertStringContainsString('src="'.image_url('banners/bestsellers.jpg').'"', $html);
        $this->assertStringContainsString('vr-promo', $html);
    }

    public function test_each_section_slot_renders_its_own_banner(): void
    {
        $this->promo('after_featured', ['image' => 'banners/featured.jpg']);
        $this->promo('after_bestsellers', ['image' => 'banners/bestsellers.jpg']);
        $this->promo('after_new_arrivals', ['image' => 'banners/new.jpg']);

        $html = $this->html();

        foreach (['featured', 'bestsellers', 'new'] as $slug) {
            $this->assertStringContainsString(
                'src="'.image_url("banners/{$slug}.jpg").'"',
                $html,
                "Expected the {$slug} promotional banner to render."
            );
        }
    }

    public function test_a_banner_appears_after_the_matching_section_heading(): void
    {
        // Bestsellers and New Arrivals fall back to the featured set when they
        // are empty, so one featured product makes all three sections render.
        Product::factory()->active()->create([
            'is_featured' => true,
            'stock' => 5,
        ]);
        $this->promo('after_bestsellers', ['image' => 'banners/bestsellers.jpg']);

        $html = $this->html();

        $bestsellersHeading = strpos($html, '>Bestsellers<');
        $newArrivalsHeading = strpos($html, '>New Arrivals<');
        $promoImage = strpos($html, image_url('banners/bestsellers.jpg'));

        $this->assertNotFalse($bestsellersHeading, 'The Bestsellers section did not render.');
        $this->assertNotFalse($newArrivalsHeading, 'The New Arrivals section did not render.');
        $this->assertNotFalse($promoImage);

        $this->assertGreaterThan(
            $bestsellersHeading,
            $promoImage,
            'The promotional banner must render below the Bestsellers heading.'
        );
        $this->assertLessThan(
            $newArrivalsHeading,
            $promoImage,
            'The promotional banner must render above the New Arrivals heading.'
        );
    }

    public function test_only_the_lowest_sort_order_banner_renders_for_a_slot(): void
    {
        $this->promo('after_bestsellers', ['image' => 'banners/second.jpg', 'sort_order' => 2]);
        $this->promo('after_bestsellers', ['image' => 'banners/first.jpg', 'sort_order' => 1]);

        $html = $this->html();

        $this->assertStringContainsString('src="'.image_url('banners/first.jpg').'"', $html);
        $this->assertStringNotContainsString(image_url('banners/second.jpg'), $html);
    }

    public function test_an_unassigned_slot_renders_no_promotional_strip(): void
    {
        $this->promo('after_bestsellers');

        $html = $this->html();

        $this->assertSame(1, substr_count($html, 'vr-promo-img'));
    }

    public function test_no_promotional_banners_means_no_promotional_markup(): void
    {
        $this->assertStringNotContainsString('vr-promo-img', $this->html());
    }

    public function test_inactive_banners_do_not_render(): void
    {
        $this->promo('after_bestsellers', ['status' => 'inactive']);

        $this->assertStringNotContainsString('vr-promo-img', $this->html());
    }

    public function test_expired_banners_do_not_render(): void
    {
        $this->promo('after_bestsellers', ['expires_at' => now()->subDay()]);

        $this->assertStringNotContainsString('vr-promo-img', $this->html());
    }

    public function test_banners_that_have_not_started_do_not_render(): void
    {
        $this->promo('after_bestsellers', ['starts_at' => now()->addDay()]);

        $this->assertStringNotContainsString('vr-promo-img', $this->html());
    }

    public function test_a_banner_inside_its_scheduling_window_renders(): void
    {
        $this->promo('after_bestsellers', [
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addDay(),
        ]);

        $this->assertStringContainsString('vr-promo-img', $this->html());
    }

    public function test_hero_banners_are_never_treated_as_promotional_strips(): void
    {
        Banner::query()->create([
            'image' => 'banners/hero.jpg',
            'link' => 'https://example.com',
            'type' => 'hero',
            'position' => 'home_top',
            'sort_order' => 1,
            'status' => 'active',
        ]);

        $html = $this->html();

        $this->assertStringContainsString('vr-hero-pic', $html);
        $this->assertStringNotContainsString('vr-promo-img', $html);
    }

    public function test_a_hero_positioned_promotional_banner_does_not_leak_into_a_section(): void
    {
        $this->promo('home_top');

        $this->assertStringNotContainsString('vr-promo-img', $this->html());
    }

    public function test_the_section_type_renders_like_promotional(): void
    {
        $this->promo('after_bestsellers', ['type' => 'section']);

        $this->assertStringContainsString('vr-promo-img', $this->html());
    }

    public function test_the_title_is_used_as_the_alt_text(): void
    {
        $this->promo('after_bestsellers', ['title' => 'Winter Ayurveda sale']);

        $this->assertStringContainsString('alt="Winter Ayurveda sale"', $this->html());
    }

    public function test_alt_text_falls_back_when_no_title_is_set(): void
    {
        $this->promo('after_bestsellers', ['title' => null]);

        $this->assertStringContainsString('alt="'.store_name().' promotional banner"', $this->html());
    }

    public function test_the_mobile_image_is_served_to_phones_when_present(): void
    {
        $this->promo('after_bestsellers', [
            'image' => 'banners/promo-desktop.jpg',
            'mobile_image' => 'banners/promo-mobile.jpg',
        ]);

        $html = $this->html();

        $this->assertStringContainsString(
            '<source media="(max-width: 767.98px)" srcset="'.image_url('banners/promo-mobile.jpg').'">',
            $html
        );
        $this->assertStringContainsString('src="'.image_url('banners/promo-desktop.jpg').'"', $html);
    }

    public function test_no_mobile_source_is_emitted_when_none_is_set(): void
    {
        $this->promo('after_bestsellers');

        $html = $this->html();

        $this->assertStringContainsString('vr-promo-img', $html);
        $this->assertSame(
            0,
            preg_match('/<source media="\(max-width: 767\.98px\)"[^>]*vr-promo/u', $html),
            'A promotional banner without a mobile image must not emit a <source> element.'
        );
    }

    public function test_the_banner_is_wrapped_in_its_link(): void
    {
        $this->promo('after_bestsellers', ['link' => 'https://example.com/holiday']);

        $this->assertStringContainsString('href="https://example.com/holiday"', $this->html());
    }

    public function test_a_banner_without_a_link_still_renders_the_image(): void
    {
        $this->promo('after_bestsellers', ['link' => null]);

        $html = $this->html();

        $this->assertStringContainsString('vr-promo-img', $html);
    }

    public function test_the_homepage_still_has_exactly_one_h1_with_promotional_banners(): void
    {
        $this->promo('after_featured');
        $this->promo('after_bestsellers');
        $this->promo('after_new_arrivals');

        $this->assertSame(1, substr_count($this->html(), '<h1'));
    }
}
