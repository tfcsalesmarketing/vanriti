<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchSuggestTest extends TestCase
{
    use RefreshDatabase;

    public function test_suggest_returns_empty_for_short_queries(): void
    {
        $response = $this->getJson(route('search.suggest', ['q' => 'a']));

        $response->assertOk();
        $response->assertJson([
            'query' => 'a',
            'products' => [],
            'categories' => [],
        ]);
    }

    public function test_suggest_matches_products_by_name_and_category(): void
    {
        $category = Category::factory()->create([
            'name' => 'Hair Care Cleansers',
            'slug' => 'hair-care-cleansers',
            'status' => 'active',
        ]);

        $product1 = Product::factory()->active()->create([
            'name' => 'VANRITI Organic Amla Hair Oil',
            'sku' => 'VNR-OIL-01',
            'selling_price' => 299.00,
            'mrp' => 399.00,
            'status' => 'active',
        ]);
        $product1->categories()->attach($category);

        $product2 = Product::factory()->active()->create([
            'name' => 'Rose Water Toner',
            'sku' => 'VNR-TONER-02',
            'status' => 'active',
        ]);

        // Search for "Amla"
        $response = $this->getJson(route('search.suggest', ['q' => 'Amla']));
        $response->assertOk();
        $response->assertJsonFragment(['name' => 'VANRITI Organic Amla Hair Oil']);
        $response->assertJsonMissing(['name' => 'Rose Water Toner']);

        // Search for "Hair Care" (matches category and product)
        $catResponse = $this->getJson(route('search.suggest', ['q' => 'Hair Care']));
        $catResponse->assertOk();
        $catResponse->assertJsonFragment(['name' => 'Hair Care Cleansers']);
        $catResponse->assertJsonFragment(['name' => 'VANRITI Organic Amla Hair Oil']);
    }

    public function test_suggest_matches_products_by_sku(): void
    {
        $product = Product::factory()->active()->create([
            'name' => 'Bhringraj Powder 200g',
            'sku' => 'VNRT-BHR-200',
            'status' => 'active',
        ]);

        $response = $this->getJson(route('search.suggest', ['q' => 'VNRT-BHR']));
        $response->assertOk();
        $response->assertJsonFragment(['name' => 'Bhringraj Powder 200g']);
    }

    public function test_suggest_excludes_inactive_products_and_categories(): void
    {
        $inactiveCat = Category::factory()->create([
            'name' => 'Inactive Specialty Category',
            'slug' => 'inactive-specialty-category',
            'status' => 'inactive',
        ]);

        $inactiveProduct = Product::factory()->create([
            'name' => 'Archived Hair Serum',
            'status' => 'inactive',
        ]);

        $response = $this->getJson(route('search.suggest', ['q' => 'Specialty']));
        $response->assertOk();
        $response->assertJsonMissing(['name' => 'Inactive Specialty Category']);

        $prodResponse = $this->getJson(route('search.suggest', ['q' => 'Archived']));
        $prodResponse->assertOk();
        $prodResponse->assertJsonMissing(['name' => 'Archived Hair Serum']);
    }
}

