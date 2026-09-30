<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Banner;
use Database\Seeders\BannerSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BannerPositionValidationTest extends TestCase
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
            'type' => 'promotional',
            'position' => 'after_bestsellers',
            'sort_order' => 1,
            'status' => 'active',
        ], $overrides);
    }

    public function test_a_promotional_banner_can_be_assigned_to_a_section_slot(): void
    {
        $this->post(route('admin.banners.store'), $this->payload([
            'image' => UploadedFile::fake()->image('promo.jpg'),
        ]))->assertRedirect(route('admin.banners.index'));

        $this->assertDatabaseHas('banners', [
            'type' => 'promotional',
            'position' => 'after_bestsellers',
        ]);
    }

    public function test_every_section_slot_is_accepted(): void
    {
        foreach (array_keys(Banner::SECTION_POSITIONS) as $position) {
            $this->post(route('admin.banners.store'), $this->payload([
                'position' => $position,
                'image' => UploadedFile::fake()->image('promo.jpg'),
            ]))->assertRedirect(route('admin.banners.index'));

            $this->assertDatabaseHas('banners', ['position' => $position]);
        }
    }

    public function test_a_hero_banner_still_uses_the_home_top_position(): void
    {
        $this->post(route('admin.banners.store'), $this->payload([
            'type' => 'hero',
            'position' => 'home_top',
            'image' => UploadedFile::fake()->image('hero.jpg'),
        ]))->assertRedirect(route('admin.banners.index'));

        $this->assertDatabaseHas('banners', [
            'type' => 'hero',
            'position' => 'home_top',
        ]);
    }

    public function test_a_hero_banner_cannot_be_pointed_at_a_section_slot(): void
    {
        $this->post(route('admin.banners.store'), $this->payload([
            'type' => 'hero',
            'position' => 'after_bestsellers',
            'image' => UploadedFile::fake()->image('hero.jpg'),
        ]))->assertSessionHasErrors('position');

        $this->assertSame(0, Banner::query()->count());
    }

    public function test_a_promotional_banner_cannot_be_pointed_at_the_hero_position(): void
    {
        $this->post(route('admin.banners.store'), $this->payload([
            'type' => 'promotional',
            'position' => 'home_top',
            'image' => UploadedFile::fake()->image('promo.jpg'),
        ]))->assertSessionHasErrors('position');

        $this->assertSame(0, Banner::query()->count());
    }

    public function test_an_unknown_position_is_rejected(): void
    {
        $this->post(route('admin.banners.store'), $this->payload([
            'position' => 'after_everything',
            'image' => UploadedFile::fake()->image('promo.jpg'),
        ]))->assertSessionHasErrors('position');

        $this->assertSame(0, Banner::query()->count());
    }

    public function test_the_historical_home_hero_position_is_rejected(): void
    {
        $this->post(route('admin.banners.store'), $this->payload([
            'type' => 'hero',
            'position' => 'home_hero',
            'image' => UploadedFile::fake()->image('hero.jpg'),
        ]))->assertSessionHasErrors('position');

        $this->assertSame(0, Banner::query()->count());
    }

    public function test_the_title_is_persisted(): void
    {
        $this->post(route('admin.banners.store'), $this->payload([
            'title' => 'Winter Ayurveda Sale',
            'image' => UploadedFile::fake()->image('promo.jpg'),
        ]))->assertRedirect(route('admin.banners.index'));

        $this->assertDatabaseHas('banners', ['title' => 'Winter Ayurveda Sale']);
    }

    public function test_the_title_is_optional(): void
    {
        $this->post(route('admin.banners.store'), $this->payload([
            'image' => UploadedFile::fake()->image('promo.jpg'),
        ]))->assertRedirect(route('admin.banners.index'));

        $this->assertDatabaseHas('banners', ['position' => 'after_bestsellers']);
    }

    public function test_the_title_can_be_changed_on_update(): void
    {
        $banner = Banner::query()->create([
            'title' => 'Old title',
            'image' => 'banners/promo.jpg',
            'link' => 'https://example.com/deal',
            'type' => 'promotional',
            'position' => 'after_bestsellers',
            'sort_order' => 1,
            'status' => 'active',
        ]);

        $this->put(route('admin.banners.update', $banner), $this->payload([
            'title' => 'New title',
        ]))->assertRedirect(route('admin.banners.index'));

        $this->assertSame('New title', $banner->fresh()->title);
    }

    public function test_updating_a_banner_into_an_invalid_position_is_rejected(): void
    {
        $banner = Banner::query()->create([
            'image' => 'banners/promo.jpg',
            'link' => 'https://example.com/deal',
            'type' => 'promotional',
            'position' => 'after_bestsellers',
            'sort_order' => 1,
            'status' => 'active',
        ]);

        $this->put(route('admin.banners.update', $banner), $this->payload([
            'type' => 'hero',
            'position' => 'after_new_arrivals',
        ]))->assertSessionHasErrors('position');

        $this->assertSame('after_bestsellers', $banner->fresh()->position);
    }

    public function test_a_banner_can_be_moved_between_section_slots(): void
    {
        $banner = Banner::query()->create([
            'image' => 'banners/promo.jpg',
            'link' => 'https://example.com/deal',
            'type' => 'promotional',
            'position' => 'after_bestsellers',
            'sort_order' => 1,
            'status' => 'active',
        ]);

        $this->put(route('admin.banners.update', $banner), $this->payload([
            'position' => 'after_new_arrivals',
        ]))->assertRedirect(route('admin.banners.index'));

        $this->assertSame('after_new_arrivals', $banner->fresh()->position);
    }

    public function test_the_seeded_hero_banners_use_a_valid_position(): void
    {
        $this->seed(BannerSeeder::class);

        $this->assertGreaterThan(0, Banner::query()->count());

        foreach (Banner::query()->where('type', 'hero')->get() as $banner) {
            $this->assertContains(
                $banner->position,
                array_keys(Banner::HERO_POSITIONS),
                "Seeded hero banner '{$banner->title}' has an unusable position."
            );
        }
    }

    public function test_reseeding_repairs_a_banner_left_on_the_old_home_hero_position(): void
    {
        $banner = Banner::query()->create([
            'title' => 'Clean Beauty, Rooted in Ayurveda',
            'image' => 'https://placehold.co/1600x600/1f3d2b/f7f4ee/png?text=VANRITI+HERBAL+GLOW',
            'mobile_image' => 'https://placehold.co/600x600/1f3d2b/f7f4ee/png?text=VANRITI',
            'link' => '/shop',
            'type' => 'hero',
            'position' => 'home_hero',
            'sort_order' => 1,
            'status' => 'active',
        ]);

        $this->seed(BannerSeeder::class);

        $this->assertSame('home_top', $banner->fresh()->position);
        $this->assertSame(1, Banner::query()->where('title', 'Clean Beauty, Rooted in Ayurveda')->count());
    }

    public function test_the_seeded_hero_banners_render_in_the_homepage_carousel(): void
    {
        // Settings are seeded because rendering the homepage without them leaks
        // an output buffer (a pre-existing app issue, also seen in ShopTest),
        // which would mark this test risky.
        $this->seed(SettingsSeeder::class);
        $this->seed(BannerSeeder::class);

        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertStringContainsString('vr-hero-pic', $html);
    }

    public function test_a_legacy_banner_with_an_invalid_position_stays_editable(): void
    {
        $banner = Banner::query()->create([
            'image' => 'banners/legacy.jpg',
            'link' => 'https://example.com/deal',
            'type' => 'promotional',
            'position' => 'home_top',
            'sort_order' => 0,
            'status' => 'active',
        ]);

        $html = $this->get(route('admin.banners.edit', $banner))->assertOk()->getContent();

        $this->assertStringContainsString('which is not valid for a promotional banner', $html);
        $this->assertStringContainsString('<option value="after_featured" selected>', $html);
    }

    public function test_saving_a_legacy_banner_repositions_it_instead_of_failing(): void
    {
        $banner = Banner::query()->create([
            'image' => 'banners/legacy.jpg',
            'link' => 'https://example.com/deal',
            'type' => 'promotional',
            'position' => 'home_top',
            'sort_order' => 0,
            'status' => 'active',
        ]);

        $this->put(route('admin.banners.update', $banner), $this->payload([
            'position' => 'after_featured',
        ]))->assertRedirect(route('admin.banners.index'))->assertSessionHasNoErrors();

        $this->assertSame('after_featured', $banner->fresh()->position);
    }

    public function test_a_valid_position_is_never_overridden_on_the_edit_form(): void
    {
        $banner = Banner::query()->create([
            'image' => 'banners/promo.jpg',
            'link' => 'https://example.com/deal',
            'type' => 'promotional',
            'position' => 'after_new_arrivals',
            'sort_order' => 1,
            'status' => 'active',
        ]);

        $html = $this->get(route('admin.banners.edit', $banner))->assertOk()->getContent();

        $this->assertStringContainsString('<option value="after_new_arrivals" selected>', $html);
        $this->assertStringNotContainsString('which is not valid for a promotional banner', $html);
    }

    public function test_the_form_offers_every_position_as_a_dropdown_choice(): void
    {
        $html = $this->get(route('admin.banners.create'))->assertOk()->getContent();

        $this->assertStringContainsString('name="position"', $html);
        $this->assertStringNotContainsString('<input type="text" name="position"', $html);

        foreach (array_merge(Banner::HERO_POSITIONS, Banner::SECTION_POSITIONS) as $value => $label) {
            $this->assertStringContainsString('value="'.$value.'"', $html);
            $this->assertStringContainsString($label, $html);
        }
    }

    public function test_the_form_exposes_the_title_field(): void
    {
        $html = $this->get(route('admin.banners.create'))->assertOk()->getContent();

        $this->assertStringContainsString('name="title"', $html);
    }

    public function test_the_index_shows_a_readable_position_label(): void
    {
        Banner::query()->create([
            'image' => 'banners/promo.jpg',
            'link' => 'https://example.com/deal',
            'type' => 'promotional',
            'position' => 'after_bestsellers',
            'sort_order' => 1,
            'status' => 'active',
        ]);

        $html = $this->get(route('admin.banners.index'))->assertOk()->getContent();

        $this->assertStringContainsString('After Bestsellers section', $html);
        $this->assertStringNotContainsString('<td class="small">after_bestsellers</td>', $html);
    }
}
