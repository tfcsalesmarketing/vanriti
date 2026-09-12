<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_returns_200(): void
    {
        $this->get(route('home'))->assertOk();
    }

    public function test_shop_page_returns_200_with_seeded_product_visible(): void
    {
        $product = Product::factory()->active()->create(['name' => 'Glow Serum Supreme']);

        $response = $this->get(route('shop.index'));
        $response->assertOk();
        $response->assertSee('Glow Serum Supreme');
    }

    public function test_shop_search_filters_by_query(): void
    {
        $match = Product::factory()->active()->create(['name' => 'Vitamin C Brightening Cream']);
        Product::factory()->active()->create(['name' => 'Herbal Hair Oil']);

        $response = $this->get(route('shop.index', ['q' => 'Vitamin']));
        $response->assertOk();
        $response->assertSee('Vitamin C Brightening Cream');
        $response->assertDontSee('Herbal Hair Oil');
    }

    public function test_product_page_shows_active_product(): void
    {
        $product = Product::factory()->active()->create(['name' => 'Rose Toner Mist']);

        $this->get(route('product.show', $product))
            ->assertOk()
            ->assertSee('Rose Toner Mist');
    }

    public function test_product_page_returns_404_for_inactive_product(): void
    {
        $product = Product::factory()->create(['status' => 'inactive']);

        $this->get(route('product.show', $product))->assertNotFound();
    }

    public function test_category_page_includes_descendant_products(): void
    {
        $parent = Category::factory()->active()->create();
        $child = Category::factory()->active()->create(['parent_id' => $parent->id]);

        $product = Product::factory()->active()->create();
        $product->categories()->syncWithoutDetaching([$child->id => ['is_primary' => true]]);

        $response = $this->get(route('shop.category', $parent));
        $response->assertOk();
        $response->assertSee($product->name);
    }

    public function test_category_page_shows_direct_products(): void
    {
        $category = Category::factory()->active()->create();
        $product = Product::factory()->active()->create();
        $product->categories()->syncWithoutDetaching([$category->id => ['is_primary' => true]]);

        $this->get(route('shop.category', $category))
            ->assertOk()
            ->assertSee($product->name);
    }

    public function test_shop_pagination_works(): void
    {
        Product::factory()->count(15)->active()->create();

        $this->get(route('shop.index'))->assertOk();
    }
}
