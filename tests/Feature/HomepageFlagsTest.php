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
}
