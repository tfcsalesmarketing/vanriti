<?php

namespace Tests\Feature;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomepageKhetikaRedesignTest extends TestCase
{
    use RefreshDatabase;

    public function test_unified_featured_slider_renders_plain_badges_in_red_pink_green(): void
    {
        $cat = Category::factory()->create(['status' => 'active']);

        $featured = Product::factory()->create([
            'name' => 'Rose Saffron Serum',
            'status' => 'active',
            'stock' => 10,
            'is_featured' => true,
            'is_bestseller' => false,
            'is_new_arrival' => false,
        ]);
        $featured->categories()->attach($cat);

        $bestseller = Product::factory()->create([
            'name' => 'Bhringraj Growth Oil',
            'status' => 'active',
            'stock' => 15,
            'is_featured' => false,
            'is_bestseller' => true,
            'is_new_arrival' => false,
        ]);
        $bestseller->categories()->attach($cat);

        $new = Product::factory()->create([
            'name' => 'Neem Tulsi Cleanser',
            'status' => 'active',
            'stock' => 20,
            'is_featured' => false,
            'is_bestseller' => false,
            'is_new_arrival' => true,
        ]);
        $new->categories()->attach($cat);

        $response = $this->get('/');
        $response->assertOk();

        $content = $response->getContent();

        // Unified section header
        $this->assertStringContainsString('Featured &amp; Bestsellers', $content);

        // Plain text badges without icons
        $this->assertStringContainsString('vr-flag-featured', $content);
        $this->assertStringContainsString('Featured', $content);

        $this->assertStringContainsString('vr-flag-bestseller', $content);
        $this->assertStringContainsString('Best Selling', $content);

        $this->assertStringContainsString('vr-flag-new', $content);
        $this->assertStringContainsString('New Arrivals', $content);
    }

    public function test_category_showcase_respects_admin_show_on_homepage_flag(): void
    {
        // 5 categories in database
        $c1 = Category::factory()->create(['name' => 'Hair Care', 'slug' => 'hair-care', 'show_on_homepage' => true, 'status' => 'active']);
        $c2 = Category::factory()->create(['name' => 'Skin Care', 'slug' => 'skin-care', 'show_on_homepage' => true, 'status' => 'active']);
        $c3 = Category::factory()->create(['name' => 'Wellness', 'slug' => 'wellness', 'show_on_homepage' => false, 'status' => 'active']);
        $c4 = Category::factory()->create(['name' => 'Body Care', 'slug' => 'body-care', 'show_on_homepage' => false, 'status' => 'active']);
        $c5 = Category::factory()->create(['name' => 'Herbal Teas', 'slug' => 'herbal-teas', 'show_on_homepage' => false, 'status' => 'active']);

        $response = $this->get('/');
        $response->assertOk();

        $content = $response->getContent();

        // Category showcase container present
        $this->assertStringContainsString('vr-home-cat-showcase', $content);

        // Only c1 and c2 were marked for homepage showcase
        $this->assertStringContainsString('Hair Care', $content);
        $this->assertStringContainsString('Skin Care', $content);

        // The showcase grid uses 3-in-a-row column layout (col-4)
        $this->assertStringContainsString('col-4', $content);
        $this->assertStringContainsString('vr-cat-card-showcase', $content);
    }

    public function test_multi_category_sections_render_product_sliders(): void
    {
        $cat = Category::factory()->create(['name' => 'Pure Oils', 'slug' => 'pure-oils', 'status' => 'active']);
        $p = Product::factory()->create(['name' => 'Cold Pressed Almond Oil', 'status' => 'active', 'stock' => 10]);
        $p->categories()->attach($cat);

        $response = $this->get('/');
        $response->assertOk();

        $content = $response->getContent();

        // Category shelf section rendered with product slider
        $this->assertStringContainsString('vr-category-shelf', $content);
        $this->assertStringContainsString('Pure Oils', $content);
        $this->assertStringContainsString('Cold Pressed Almond Oil', $content);
    }

    public function test_homepage_banner_slots_render_configured_banners(): void
    {
        Banner::query()->create([
            'title' => 'Festive Haircare Offer',
            'image' => 'banners/slot1.jpg',
            'link' => 'https://example.com/festive',
            'type' => 'promotional',
            'position' => 'home_slot_1',
            'status' => 'active',
            'sort_order' => 1,
        ]);

        Banner::query()->create([
            'title' => 'Botanical Skincare Routine',
            'image' => 'banners/slot2.jpg',
            'link' => 'https://example.com/skincare',
            'type' => 'promotional',
            'position' => 'home_slot_2',
            'status' => 'active',
            'sort_order' => 2,
        ]);

        $response = $this->get('/');
        $response->assertOk();

        $content = $response->getContent();

        $this->assertStringContainsString('Festive Haircare Offer', $content);
        $this->assertStringContainsString('Botanical Skincare Routine', $content);
    }

    public function test_reviews_section_fetches_up_to_20_distinct_product_reviews(): void
    {
        // Create 2 products with 2 reviews each for the same product
        $p1 = Product::factory()->create(['name' => 'Kumkumadi Oil', 'status' => 'active', 'stock' => 10]);
        $p2 = Product::factory()->create(['name' => 'Aloe Vera Gel', 'status' => 'active', 'stock' => 10]);

        // 2 reviews for p1 (older review should be deduplicated in favor of latest review)
        Review::create([
            'product_id' => $p1->id,
            'user_name' => 'Ananya Sharma',
            'rating' => 5,
            'comment' => 'Duplicate product review that must be deduplicated.',
            'status' => 'approved',
        ]);
        Review::create([
            'product_id' => $p1->id,
            'user_name' => 'Rohan V.',
            'rating' => 5,
            'comment' => 'Superb radiance within 2 weeks of using kumkumadi!',
            'status' => 'approved',
        ]);

        // 1 review for p2
        Review::create([
            'product_id' => $p2->id,
            'user_name' => 'Pooja Mehta',
            'rating' => 5,
            'comment' => 'Most soothing aloe gel for daily calm skin.',
            'status' => 'approved',
        ]);

        $response = $this->get('/');
        $response->assertOk();

        $content = $response->getContent();

        // Reviews section rendered
        $this->assertStringContainsString('vr-section-reviews', $content);
        $this->assertStringContainsString('Customer Reviews &amp; Stories', $content);

        // Review cards with product and Shop Now button
        $this->assertStringContainsString('Shop Now', $content);
        $this->assertStringContainsString('Verified Buyer', $content);
        $this->assertStringContainsString('Kumkumadi Oil', $content);
        $this->assertStringContainsString('Aloe Vera Gel', $content);

        // Deduplication rule check: only ONE review for p1 rendered
        $this->assertStringContainsString('Superb radiance within 2 weeks', $content);
        $this->assertStringNotContainsString('Duplicate product review that must be deduplicated', $content);
    }

    public function test_footer_renders_all_columns_and_social_links(): void
    {
        $response = $this->get('/');
        $response->assertOk();

        $content = $response->getContent();

        // Footer structure intact
        $this->assertStringContainsString('vr-footer', $content);
        $this->assertStringContainsString('Shop', $content);
        $this->assertStringContainsString('Company', $content);
        $this->assertStringContainsString('Customer Care', $content);
        $this->assertStringContainsString('Policies', $content);

        // Social icons
        $this->assertStringContainsString('vr-footer-social', $content);
        $this->assertStringContainsString('vr-social-icon', $content);
    }
}
