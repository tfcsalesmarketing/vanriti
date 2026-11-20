<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductAPlusVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_without_a_plus_enabled_does_not_render_a_plus_section(): void
    {
        $category = Category::factory()->create(['name' => 'Hair Care', 'slug' => 'hair-care']);
        $product = Product::factory()->create([
            'name' => 'Amla Powder Test',
            'status' => 'active',
            'has_a_plus' => false,
            'a_plus_content' => null,
        ]);
        $product->categories()->attach($category->id, ['is_primary' => true]);

        // Model helper check
        $this->assertEmpty($product->getEffectiveAPlusModules());

        // Storefront PDP rendering check
        $response = $this->get(route('product.show', $product->slug));
        $response->assertOk();
        $response->assertDontSee('id="vrProductAPlus"', false);
        $response->assertDontSee('Enhanced Ayurvedic Experience');
    }

    public function test_product_with_a_plus_enabled_renders_a_plus_section(): void
    {
        $category = Category::factory()->create(['name' => 'Hair Care', 'slug' => 'hair-care']);
        $product = Product::factory()->create([
            'name' => 'Bhringraj Powder Test',
            'status' => 'active',
            'has_a_plus' => true,
            'a_plus_content' => [
                'modules' => [
                    [
                        'type' => 'hero_banner',
                        'eyebrow' => 'Traditional Botanical Care',
                        'title' => 'Pure Bhringraj Herbal Hair Nourishment',
                        'description' => 'Ayurvedic botanical powder crafted from whole leaves.',
                        'image' => 'https://example.com/banner.jpg',
                    ],
                ],
            ],
        ]);
        $product->categories()->attach($category->id, ['is_primary' => true]);

        // Model helper check
        $this->assertNotEmpty($product->getEffectiveAPlusModules());

        // Storefront PDP rendering check
        $response = $this->get(route('product.show', $product->slug));
        $response->assertOk();
        $response->assertSee('id="vrProductAPlus"', false);
        $response->assertSee('Pure Bhringraj Herbal Hair Nourishment');
    }
}
