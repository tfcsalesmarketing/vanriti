<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Category;
use App\Models\MarketplaceClick;
use App\Models\Product;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceClickTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $superAdmin;
    protected Product $productWithBoth;
    protected Product $productWithAmazonOnly;
    protected Product $productWithFlipkartOnly;
    protected Product $productWithNoLinks;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->superAdmin = Admin::factory()->superAdmin()->create();

        $this->category = Category::factory()->create(['name' => 'Hair Care']);

        $this->productWithBoth = Product::factory()->create([
            'name' => 'Amla & Bhringraj Hair Oil',
            'sku' => 'VAN-OIL-001',
            'status' => 'active',
            'stock' => 50,
            'amazon_url' => 'https://www.amazon.in/dp/B0EXAMPLE1',
            'flipkart_url' => 'https://www.flipkart.com/p/itmEXAMPLE1',
        ]);
        $this->productWithBoth->categories()->attach($this->category->id, ['is_primary' => true]);

        $this->productWithAmazonOnly = Product::factory()->create([
            'name' => 'Pure Shikakai Powder',
            'sku' => 'VAN-SHI-002',
            'status' => 'active',
            'stock' => 20,
            'amazon_url' => 'https://www.amazon.in/dp/B0EXAMPLE2',
            'flipkart_url' => null,
        ]);
        $this->productWithAmazonOnly->categories()->attach($this->category->id, ['is_primary' => true]);

        $this->productWithFlipkartOnly = Product::factory()->create([
            'name' => 'Organic Multani Mitti',
            'sku' => 'VAN-MUL-003',
            'status' => 'inactive',
            'stock' => 0,
            'amazon_url' => null,
            'flipkart_url' => 'https://www.flipkart.com/p/itmEXAMPLE3',
        ]);

        $this->productWithNoLinks = Product::factory()->create([
            'name' => 'Rose Petal Face Mist',
            'sku' => 'VAN-ROS-004',
            'status' => 'active',
            'stock' => 15,
            'amazon_url' => null,
            'flipkart_url' => null,
        ]);
    }

    public function test_record_endpoint_logs_amazon_click(): void
    {
        $response = $this->postJson(route('marketplace.click'), [
            'product_id' => $this->productWithBoth->id,
            'marketplace' => 'amazon',
        ], [
            'User-Agent' => 'TestBrowser/1.0',
            'Referer' => 'http://localhost/products/' . $this->productWithBoth->slug,
        ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('marketplace_clicks', [
            'product_id' => $this->productWithBoth->id,
            'sku' => 'VAN-OIL-001',
            'marketplace' => 'amazon',
        ]);
    }

    public function test_record_endpoint_logs_flipkart_click(): void
    {
        $response = $this->postJson(route('marketplace.click'), [
            'product_id' => $this->productWithBoth->id,
            'marketplace' => 'flipkart',
        ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('marketplace_clicks', [
            'product_id' => $this->productWithBoth->id,
            'sku' => 'VAN-OIL-001',
            'marketplace' => 'flipkart',
        ]);
    }

    public function test_record_endpoint_validates_required_fields_and_valid_marketplaces(): void
    {
        // Missing fields
        $this->postJson(route('marketplace.click'), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['product_id', 'marketplace']);

        // Invalid marketplace
        $this->postJson(route('marketplace.click'), [
            'product_id' => $this->productWithBoth->id,
            'marketplace' => 'ebay',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['marketplace']);

        // Non-existent product
        $this->postJson(route('marketplace.click'), [
            'product_id' => 999999,
            'marketplace' => 'amazon',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['product_id']);
    }

    public function test_guest_is_redirected_from_admin_marketplace_clicks(): void
    {
        $this->get(route('admin.marketplace-clicks.index'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_view_marketplace_clicks_dashboard(): void
    {
        // Seed some click data
        MarketplaceClick::factory()->create([
            'product_id' => $this->productWithBoth->id,
            'sku' => $this->productWithBoth->sku,
            'marketplace' => 'amazon',
        ]);
        MarketplaceClick::factory()->create([
            'product_id' => $this->productWithBoth->id,
            'sku' => $this->productWithBoth->sku,
            'marketplace' => 'flipkart',
        ]);
        MarketplaceClick::factory()->create([
            'product_id' => $this->productWithAmazonOnly->id,
            'sku' => $this->productWithAmazonOnly->sku,
            'marketplace' => 'amazon',
        ]);

        $response = $this->actingAs($this->superAdmin, 'admin')
            ->get(route('admin.marketplace-clicks.index'));

        $response->assertOk()
            ->assertViewIs('admin.marketplace_clicks.index')
            ->assertViewHas('kpis', function (array $kpis) {
                return $kpis['total_clicks'] === 3
                    && $kpis['amazon_clicks'] === 2
                    && $kpis['flipkart_clicks'] === 1
                    && $kpis['clicked_skus_count'] === 2;
            })
            ->assertSee('Amla &amp; Bhringraj Hair Oil', false)
            ->assertSee('VAN-OIL-001')
            ->assertSee('Amazon Clicks')
            ->assertSee('Flipkart Clicks');
    }

    public function test_admin_can_filter_by_search_keyword(): void
    {
        $response = $this->actingAs($this->superAdmin, 'admin')
            ->get(route('admin.marketplace-clicks.index', ['search' => 'Shikakai']));

        $response->assertOk()
            ->assertSee('Pure Shikakai Powder')
            ->assertDontSee('Amla & Bhringraj Hair Oil');
    }

    public function test_admin_can_filter_by_marketplace_link_status(): void
    {
        // Has both
        $bothResponse = $this->actingAs($this->superAdmin, 'admin')
            ->get(route('admin.marketplace-clicks.index', ['marketplace_link' => 'has_both']));
        $bothResponse->assertOk()
            ->assertSee('Amla &amp; Bhringraj Hair Oil', false)
            ->assertDontSee('Pure Shikakai Powder')
            ->assertDontSee('Rose Petal Face Mist');

        // Has none
        $noneResponse = $this->actingAs($this->superAdmin, 'admin')
            ->get(route('admin.marketplace-clicks.index', ['marketplace_link' => 'has_none']));
        $noneResponse->assertOk()
            ->assertSee('Rose Petal Face Mist')
            ->assertDontSee('Amla & Bhringraj Hair Oil');
    }

    public function test_admin_can_filter_by_click_activity(): void
    {
        // Click only for oil product
        MarketplaceClick::factory()->create([
            'product_id' => $this->productWithBoth->id,
            'sku' => $this->productWithBoth->sku,
            'marketplace' => 'amazon',
        ]);

        $withClicksResponse = $this->actingAs($this->superAdmin, 'admin')
            ->get(route('admin.marketplace-clicks.index', ['activity' => 'with_clicks']));
        $withClicksResponse->assertOk()
            ->assertSee('Amla &amp; Bhringraj Hair Oil', false)
            ->assertDontSee('Pure Shikakai Powder');

        $zeroClicksResponse = $this->actingAs($this->superAdmin, 'admin')
            ->get(route('admin.marketplace-clicks.index', ['activity' => 'zero_clicks']));
        $zeroClicksResponse->assertOk()
            ->assertSee('Pure Shikakai Powder')
            ->assertDontSee('Amla &amp; Bhringraj Hair Oil', false);
    }

    public function test_admin_can_filter_by_stock_and_status(): void
    {
        $outOfStockResponse = $this->actingAs($this->superAdmin, 'admin')
            ->get(route('admin.marketplace-clicks.index', [
                'stock' => 'out_of_stock',
                'status' => 'inactive',
            ]));

        $outOfStockResponse->assertOk()
            ->assertSee('Organic Multani Mitti')
            ->assertDontSee('Amla & Bhringraj Hair Oil');
    }

    public function test_admin_can_export_csv_report(): void
    {
        MarketplaceClick::factory()->create([
            'product_id' => $this->productWithBoth->id,
            'sku' => $this->productWithBoth->sku,
            'marketplace' => 'amazon',
        ]);

        $response = $this->actingAs($this->superAdmin, 'admin')
            ->get(route('admin.marketplace-clicks.export'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();
        $this->assertStringContainsString('Product ID', $content);
        $this->assertStringContainsString('VAN-OIL-001', $content);
        $this->assertStringContainsString('Amla & Bhringraj Hair Oil', $content);
    }

    public function test_admin_can_filter_by_date_range_presets_and_custom(): void
    {
        // Click yesterday
        MarketplaceClick::factory()->create([
            'product_id' => $this->productWithBoth->id,
            'sku' => $this->productWithBoth->sku,
            'marketplace' => 'amazon',
            'created_at' => now()->subDay()->startOfDay()->addHours(2),
        ]);

        // Click today
        MarketplaceClick::factory()->create([
            'product_id' => $this->productWithAmazonOnly->id,
            'sku' => $this->productWithAmazonOnly->sku,
            'marketplace' => 'amazon',
            'created_at' => now()->startOfDay()->addMinutes(10),
        ]);

        // Today preset: should include today's click, exclude yesterday's
        $todayRes = $this->actingAs($this->superAdmin, 'admin')
            ->get(route('admin.marketplace-clicks.index', ['date_range' => 'today']));
        $todayRes->assertOk();
        $this->assertEquals(1, $todayRes->viewData('kpis')['total_clicks']);

        // Yesterday preset
        $yesterdayRes = $this->actingAs($this->superAdmin, 'admin')
            ->get(route('admin.marketplace-clicks.index', ['date_range' => 'yesterday']));
        $yesterdayRes->assertOk();
        $this->assertEquals(1, $yesterdayRes->viewData('kpis')['total_clicks']);

        // Custom date range
        $customRes = $this->actingAs($this->superAdmin, 'admin')
            ->get(route('admin.marketplace-clicks.index', [
                'date_range' => 'custom',
                'date_from' => now()->subDays(2)->format('Y-m-d'),
                'date_to' => now()->format('Y-m-d'),
            ]));
        $customRes->assertOk();
        $this->assertEquals(2, $customRes->viewData('kpis')['total_clicks']);
    }

    public function test_admin_can_sort_report_data(): void
    {
        // 5 clicks on productWithAmazonOnly
        MarketplaceClick::factory()->count(5)->create([
            'product_id' => $this->productWithAmazonOnly->id,
            'sku' => $this->productWithAmazonOnly->sku,
            'marketplace' => 'amazon',
        ]);

        // 2 clicks on productWithBoth (1 amazon, 1 flipkart)
        MarketplaceClick::factory()->create([
            'product_id' => $this->productWithBoth->id,
            'sku' => $this->productWithBoth->sku,
            'marketplace' => 'flipkart',
        ]);

        $sortTotalRes = $this->actingAs($this->superAdmin, 'admin')
            ->get(route('admin.marketplace-clicks.index', ['sort' => 'total_desc']));
        $sortTotalRes->assertOk();
        $products = $sortTotalRes->viewData('products');
        $this->assertEquals($this->productWithAmazonOnly->id, $products->first()->id);

        $sortFlipkartRes = $this->actingAs($this->superAdmin, 'admin')
            ->get(route('admin.marketplace-clicks.index', ['sort' => 'flipkart_desc']));
        $sortFlipkartRes->assertOk();
        $productsFlipkart = $sortFlipkartRes->viewData('products');
        $this->assertEquals($this->productWithBoth->id, $productsFlipkart->first()->id);
    }
}
