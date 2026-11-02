<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomepageFlagsTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_respects_product_flags_strictly(): void
    {
        $cat = Category::factory()->create(['name' => 'Hair Care', 'slug' => 'hair-care']);

        $featuredProduct = Product::factory()->create([
            'name' => 'Signature Amla Hair Oil',
            'status' => 'active',
            'stock' => 10,
            'is_featured' => true,
            'is_bestseller' => false,
            'is_new_arrival' => false,
        ]);
        $featuredProduct->categories()->attach($cat);

        $bestsellerProduct = Product::factory()->create([
            'name' => 'Bestseller Bhringraj Mask',
            'status' => 'active',
            'stock' => 15,
            'is_featured' => false,
            'is_bestseller' => true,
            'is_new_arrival' => false,
        ]);
        $bestsellerProduct->categories()->attach($cat);

        $newProduct = Product::factory()->create([
            'name' => 'Fresh Hibiscus Shampoo Bar',
            'status' => 'active',
            'stock' => 20,
            'is_featured' => false,
            'is_bestseller' => false,
            'is_new_arrival' => true,
        ]);
        $newProduct->categories()->attach($cat);

        $unflaggedProduct = Product::factory()->create([
            'name' => 'Unflagged Neem Powder',
            'status' => 'active',
            'stock' => 5,
            'is_featured' => false,
            'is_bestseller' => false,
            'is_new_arrival' => false,
        ]);
        $unflaggedProduct->categories()->attach($cat);

        $response = $this->get('/');
        $response->assertOk();

        $content = $response->getContent();

        // 1. Featured section rendered with featured kicker & product
        $this->assertStringContainsString('Featured Collection', $content);
        $this->assertStringContainsString('Signature Amla Hair Oil', $content);
        $this->assertStringContainsString('Featured', $content);

        // 2. Bestsellers section rendered with bestseller kicker & product
        $this->assertStringContainsString('Our Bestsellers', $content);
        $this->assertStringContainsString('Bestseller Bhringraj Mask', $content);
        $this->assertStringContainsString('#1 Bestseller', $content);

        // 3. New Arrivals section rendered with new kicker & product
        $this->assertStringContainsString('New Arrivals', $content);
        $this->assertStringContainsString('Fresh Hibiscus Shampoo Bar', $content);
        $this->assertStringContainsString('New', $content);

        // 4. Unflagged product must NOT appear on homepage
        $this->assertStringNotContainsString('Unflagged Neem Powder', $content);
    }

    public function test_homepage_does_not_duplicate_featured_into_empty_bestsellers_or_new_arrivals(): void
    {
        $cat = Category::factory()->create();

        $featured = Product::factory()->create([
            'name' => 'Only Featured Item',
            'status' => 'active',
            'stock' => 10,
            'is_featured' => true,
            'is_bestseller' => false,
            'is_new_arrival' => false,
        ]);
        $featured->categories()->attach($cat);

        $response = $this->get('/');
        $response->assertOk();

        $content = $response->getContent();

        // Featured should be visible
        $this->assertStringContainsString('Featured Collection', $content);
        $this->assertStringContainsString('Only Featured Item', $content);

        // Bestsellers and New Arrivals should NOT be rendered when no products carry those flags
        $this->assertStringNotContainsString('Our Bestsellers', $content);
        $this->assertStringNotContainsString('New Arrivals', $content);
    }

    public function test_view_all_links_point_to_specific_flag_shop_pages(): void
    {
        $cat = Category::factory()->create();

        $featured = Product::factory()->create(['status' => 'active', 'stock' => 10, 'is_featured' => true]);
        $bestseller = Product::factory()->create(['status' => 'active', 'stock' => 10, 'is_bestseller' => true]);
        $new = Product::factory()->create(['status' => 'active', 'stock' => 10, 'is_new_arrival' => true]);

        $featured->categories()->attach($cat);
        $bestseller->categories()->attach($cat);
        $new->categories()->attach($cat);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('is_featured=1', $html);
        $this->assertStringContainsString('is_bestseller=1', $html);
        $this->assertStringContainsString('is_new_arrival=1', $html);
    }

    public function test_shop_page_filters_strictly_by_flag_parameter(): void
    {
        $cat = Category::factory()->create();

        $featured = Product::factory()->create([
            'name' => 'Royal Saffron Night Oil',
            'status' => 'active',
            'stock' => 10,
            'is_featured' => true,
            'is_bestseller' => false,
            'is_new_arrival' => false,
        ]);
        $featured->categories()->attach($cat);

        $bestseller = Product::factory()->create([
            'name' => 'Kumkumadi Radiance Face Cream',
            'status' => 'active',
            'stock' => 10,
            'is_featured' => false,
            'is_bestseller' => true,
            'is_new_arrival' => false,
        ]);
        $bestseller->categories()->attach($cat);

        $new = Product::factory()->create([
            'name' => 'Vetiver Hydrating Body Mist',
            'status' => 'active',
            'stock' => 10,
            'is_featured' => false,
            'is_bestseller' => false,
            'is_new_arrival' => true,
        ]);
        $new->categories()->attach($cat);

        // 1. View all featured products
        $resFeatured = $this->get(route('shop.index', ['is_featured' => 1]))->assertOk();
        $resFeatured->assertSee('Royal Saffron Night Oil');
        $resFeatured->assertDontSee('Kumkumadi Radiance Face Cream');
        $resFeatured->assertDontSee('Vetiver Hydrating Body Mist');
        $resFeatured->assertSee('Featured Collection');

        // 2. View all bestsellers
        $resBestseller = $this->get(route('shop.index', ['is_bestseller' => 1]))->assertOk();
        $resBestseller->assertSee('Kumkumadi Radiance Face Cream');
        $resBestseller->assertDontSee('Royal Saffron Night Oil');
        $resBestseller->assertDontSee('Vetiver Hydrating Body Mist');
        $resBestseller->assertSee('Bestsellers');

        // 3. View all new arrivals
        $resNew = $this->get(route('shop.index', ['is_new_arrival' => 1]))->assertOk();
        $resNew->assertSee('Vetiver Hydrating Body Mist');
        $resNew->assertDontSee('Royal Saffron Night Oil');
        $resNew->assertDontSee('Kumkumadi Radiance Face Cream');
        $resNew->assertSee('New Arrivals');

        // 4. View all with filter query parameter (e.g. ?filter=bestseller)
        $resFilter = $this->get(route('shop.index', ['filter' => 'bestseller']))->assertOk();
        $resFilter->assertSee('Kumkumadi Radiance Face Cream');
        $resFilter->assertDontSee('Royal Saffron Night Oil');
    }
}

