<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Category;
use App\Models\Product;
use App\Models\Role;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductAPlusContentTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;
    protected Product $product;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(SettingsSeeder::class);

        $this->admin = Admin::factory()->create([
            'status' => 'active',
        ]);
        $this->admin->roles()->attach(Role::where('slug', 'super-admin')->firstOrFail());

        $this->category = Category::create([
            'name' => 'Skin Care',
            'slug' => 'skin-care',
            'status' => 'active',
        ]);

        $this->product = Product::create([
            'name' => 'Vanriti Kumkumadi Tailam',
            'slug' => 'vanriti-kumkumadi-tailam',
            'sku' => 'VAN-KUM-001',
            'status' => 'active',
            'mrp' => 1499.00,
            'selling_price' => 1199.00,
            'stock' => 15,
            'highlights' => '100% Ayurvedic Saffron Oil formulation.',
            'ingredients' => 'Kashmiri Saffron, Red Sandalwood, Manjistha, Sesame Oil.',
            'benefits' => 'Brightens skin, reduces dark spots and pigmentation.',
            'how_to_use' => 'Apply 3-4 drops nightly after cleansing.',
        ]);
        $this->product->categories()->attach($this->category->id, ['is_primary' => true]);
    }

    public function test_admin_can_save_custom_a_plus_content(): void
    {
        $payload = [
            'hero_banner' => [
                'eyebrow' => 'Ancient Radiance Secret',
                'title' => 'Handcrafted Golden Elixir',
                'description' => '16 rare botanicals boiled slowly over 72 hours.',
                'image' => 'https://example.com/banner.jpg',
            ],
            'features_grid' => [
                'title' => 'The Vanriti Purity Promise',
                'subtitle' => 'Classical methods for modern skin health.',
                'items' => [
                    [
                        'icon' => 'ri-drop-line',
                        'title' => '100% Cold-Pressed',
                        'description' => 'Never heated to preserve phyto-nutrients.',
                    ],
                    [
                        'icon' => 'ri-shield-check-line',
                        'title' => 'Ayush Certified',
                        'description' => 'Lab tested for purity.',
                    ],
                ],
            ],
            'split_story' => [
                'subtitle' => 'Sacred Kashmiri Herbs',
                'title' => 'The Botanical Extraction Ritual',
                'content' => 'Each bottle contains saffron harvested at dawn.',
                'image' => 'https://example.com/story.jpg',
                'bullets_raw' => "100% Saffron Infusion\nRed Sandalwood Core\nZero Artificial Preservatives",
            ],
            'clinical_stats' => [
                'title' => 'Proven Radiant Results',
                'subtitle' => 'Tested across 200 volunteers.',
                'stats' => [
                    ['number' => '96%', 'label' => 'Noticeable Radiance', 'sub' => 'Within 3 weeks'],
                    ['number' => '100%', 'label' => 'Natural Herbs', 'sub' => 'Zero mineral oils'],
                ],
            ],
        ];

        $response = $this->actingAs($this->admin, 'admin')
            ->put(route('admin.products.update', $this->product), [
                'name' => $this->product->name,
                'sku' => $this->product->sku,
                'status' => 'active',
                'channel' => 'vanriti',
                'mrp' => 1499.00,
                'selling_price' => 1199.00,
                'category_ids' => [$this->category->id],
                'primary_category_id' => $this->category->id,
                'has_a_plus' => '1',
                'a_plus_modules' => $payload,
            ]);

        $response->assertRedirect(route('admin.products.index'));

        $this->product->refresh();
        $this->assertTrue($this->product->has_a_plus);
        $this->assertTrue($this->product->hasAPlusContent());
        $this->assertNotEmpty($this->product->getAPlusModules());
        $this->assertSame('Handcrafted Golden Elixir', $this->product->a_plus_content['modules'][0]['title']);
    }

    public function test_storefront_renders_custom_a_plus_modules_when_enabled(): void
    {
        $this->product->update([
            'has_a_plus' => true,
            'a_plus_content' => [
                'status' => 'published',
                'modules' => [
                    [
                        'type' => 'hero_banner',
                        'sort_order' => 1,
                        'eyebrow' => 'Ancient Radiance Secret',
                        'title' => 'Handcrafted Golden Elixir',
                        'description' => '16 rare botanicals boiled slowly over 72 hours.',
                        'image' => 'https://example.com/banner.jpg',
                    ],
                    [
                        'type' => 'clinical_stats',
                        'sort_order' => 2,
                        'title' => 'Proven Radiant Results',
                        'subtitle' => 'Tested across 200 volunteers.',
                        'stats' => [
                            ['number' => '96%', 'label' => 'Noticeable Radiance', 'sub' => 'Within 3 weeks'],
                        ],
                    ],
                ],
            ],
        ]);

        $response = $this->get(route('product.show', $this->product->slug));

        $response->assertOk();
        $response->assertSee('Enhanced Ayurvedic Experience');
        $response->assertSee('Handcrafted Golden Elixir');
        $response->assertSee('Ancient Radiance Secret');
        $response->assertSee('Proven Radiant Results');
        $response->assertSee('96%');
    }

    public function test_storefront_renders_smart_fallback_when_custom_a_plus_is_disabled(): void
    {
        $this->product->update([
            'has_a_plus' => false,
            'a_plus_content' => null,
        ]);

        $response = $this->get(route('product.show', $this->product->slug));

        $response->assertOk();
        $response->assertSee('The Vanriti Purity Promise');
        $response->assertSee('100% Ayurvedic Kwath');
        $response->assertSee('Formulated for Transformative Care');
        $response->assertSee('Key Botanical Actives');
        $response->assertSee('The Ayurvedic Daily Ritual');
    }

    public function test_admin_edit_form_displays_a_plus_builder_card(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.products.edit', $this->product->slug));

        $response->assertOk();
        $response->assertSee('Amazon A+ Content Builder');
        $response->assertSee('Cinematic Brand Hero Banner');
        $response->assertSee('4-Column Ayurvedic Value Pillars');
        $response->assertSee('Split Story Block');
        $response->assertSee('Hero Botanical Ingredients Spotlight');
        $response->assertSee('3-Step Ayurvedic Daily Ritual');
        $response->assertSee('Clinical Results');
    }
}

