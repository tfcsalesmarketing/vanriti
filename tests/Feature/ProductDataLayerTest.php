<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductDataLayerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
    }

    public function test_product_page_initializes_data_layer_and_fires_one_view_item(): void
    {
        $parent = Category::factory()->create(['name' => 'Personal Care']);
        $category = Category::factory()->create(['name' => 'Skincare', 'parent_id' => $parent->id]);
        $product = Product::factory()->active()->create([
            'sku' => 'VNRT049',
            'name' => 'VANRITI Neem Powder',
            'selling_price' => 99.00,
            'mrp' => 199.00,
        ]);
        $product->categories()->syncWithoutDetaching([$category->id => ['is_primary' => true]]);

        $html = $this->get(route('product.show', $product))->assertOk()->getContent();

        $this->assertStringContainsString('window.dataLayer = window.dataLayer || [];', $html);
        $this->assertSame(1, substr_count($html, '"view_item"'));
        $this->assertStringContainsString('"item_id":"VNRT049"', $html);
        $this->assertStringContainsString('"item_name":"VANRITI Neem Powder"', $html);
        $this->assertStringContainsString('"item_brand":"VANRITI"', $html);
        $this->assertStringContainsString('"item_category":"Skincare"', $html);
        $this->assertStringContainsString('"item_category2":"Personal Care"', $html);
        $this->assertStringContainsString('"currency":"INR"', $html);
        $this->assertStringContainsString('"quantity":1', $html);
        $this->assertStringContainsString('"value":99', $html);
    }

    public function test_variant_product_uses_variant_sku_and_price_in_view_item(): void
    {
        $product = Product::factory()->active()->create([
            'sku' => 'VNRT001',
            'name' => 'VANRITI Face Wash',
            'selling_price' => 120.00,
            'mrp' => 150.00,
        ]);
        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => 'Tea Tree 150 ml',
            'sku' => 'VNRT001-TT150',
            'selling_price' => 140.00,
            'status' => 'active',
            'is_default' => true,
            'stock' => 10,
        ]);

        $html = $this->get(route('product.show', $product))->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, '"view_item"'));
        $this->assertStringContainsString('"item_id":"VNRT001-TT150"', $html);
        $this->assertStringContainsString('"item_variant":"Tea Tree 150 ml"', $html);
        $this->assertStringContainsString('"value":140', $html);
        $this->assertStringNotContainsString('"item_id":"VNRT001"', $html);
    }

    public function test_product_page_introduces_no_gtm_ga4_or_meta_scripts(): void
    {
        config(['analytics.gtm_container_id' => '']);

        $product = Product::factory()->active()->create([
            'sku' => 'VNRT050',
            'name' => 'VANRITI Basic',
            'selling_price' => 50.00,
        ]);

        $html = $this->get(route('product.show', $product))->assertOk()->getContent();

        $this->assertStringNotContainsString('googletagmanager.com', $html);
        $this->assertStringNotContainsString('gtag(', $html);
        $this->assertStringNotContainsString('connect.facebook.net', $html);
        $this->assertStringNotContainsString("fbq('", $html);
    }
}