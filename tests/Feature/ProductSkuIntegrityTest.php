<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Services\OrderService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ProductSkuIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
    }

    protected function setPixelId(string $id = 'TEST1234'): void
    {
        Setting::updateOrCreate(['key' => 'meta_pixel_id'], [
            'value' => $id,
            'group' => 'seo',
            'label' => 'Meta Pixel ID',
            'type' => 'text',
        ]);
    }

    protected function makeSkuPayload(string $sku, string $status = 'active'): array
    {
        return [
            'name' => 'Test Product '.$sku,
            'sku' => $sku,
            'status' => $status,
            'mrp' => 500,
            'selling_price' => 350,
            'category_ids' => [Category::factory()->create()->id],
        ];
    }

    protected function admin(): Admin
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        return Admin::factory()->superAdmin()->create();
    }

    protected function authenticatedAdminPost(array $payload): TestResponse
    {
        return $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.products.store'), $payload);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // 1. Admin create requires SKU
    // ──────────────────────────────────────────────────────────────────────────
    public function test_admin_product_store_requires_sku(): void
    {
        $payload = $this->makeSkuPayload('SKU-REQ-001');
        unset($payload['sku']);

        $this->authenticatedAdminPost($payload)->assertSessionHasErrors('sku');
        $this->assertDatabaseCount('products', 0);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // 2. Admin create rejects duplicate SKU
    // ──────────────────────────────────────────────────────────────────────────
    public function test_admin_product_store_rejects_duplicate_sku(): void
    {
        Product::factory()->create(['sku' => 'SKU-DUP-001']);

        $this->authenticatedAdminPost($this->makeSkuPayload('SKU-DUP-001'))
            ->assertSessionHasErrors('sku');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // 3. Admin update retains own SKU
    // ──────────────────────────────────────────────────────────────────────────
    public function test_admin_product_update_retains_own_sku(): void
    {
        $admin = $this->admin();
        $product = Product::factory()->create(['sku' => 'SKU-OWN-001']);
        $category = Category::factory()->create();
        $product->categories()->attach($category->id);

        $payload = $this->makeSkuPayload('SKU-OWN-001');
        $payload['category_ids'] = [$category->id];

        $this->actingAs($admin, 'admin')
            ->put(route('admin.products.update', $product), $payload)
            ->assertRedirect();

        $this->assertDatabaseHas('products', ['id' => $product->id, 'sku' => 'SKU-OWN-001']);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // 4. Admin update rejects another product's SKU
    // ──────────────────────────────────────────────────────────────────────────
    public function test_admin_product_update_rejects_other_products_sku(): void
    {
        $admin = $this->admin();
        $productA = Product::factory()->create(['sku' => 'SKU-TAKE-A']);
        $productB = Product::factory()->create(['sku' => 'SKU-TAKE-B']);
        $category = Category::factory()->create();
        $productA->categories()->attach($category->id);

        $payload = $this->makeSkuPayload('SKU-TAKE-B');
        $payload['category_ids'] = [$category->id];

        $this->actingAs($admin, 'admin')
            ->put(route('admin.products.update', $productA), $payload)
            ->assertSessionHasErrors('sku');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // 5. Model normalizes SKU: trim whitespace + uppercase
    // ──────────────────────────────────────────────────────────────────────────
    public function test_model_normalizes_sku_trim_and_uppercase(): void
    {
        $product = Product::factory()->create(['sku' => '  vr-fw-099  ']);
        $this->assertSame('VR-FW-099', $product->fresh()->sku);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // 6. Model converts blank-only SKU string to null
    // ──────────────────────────────────────────────────────────────────────────
    public function test_model_blank_sku_becomes_null(): void
    {
        $product = Product::factory()->create(['sku' => '   ']);
        $this->assertNull($product->fresh()->sku);

        // DB-level: multiple null SKUs are allowed (MySQL unique allows NULLs)
        Product::factory()->create(['sku' => '  ']);
        $this->assertDatabaseCount('products', 2);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // 7. Factory generates unique SKUs across many products
    // ──────────────────────────────────────────────────────────────────────────
    public function test_factory_generates_unique_skus(): void
    {
        $products = Product::factory()->active()->count(15)->create();
        $skus = $products->pluck('sku')->values()->all();

        $this->assertCount(15, array_unique($skus));
        foreach ($skus as $sku) {
            $this->assertMatchesRegularExpression('/^VR-\d{8}$/', $sku);
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // 8. ViewContent uses authoritative product SKU (non-variant path)
    // ──────────────────────────────────────────────────────────────────────────
    public function test_view_content_uses_authoritative_product_sku(): void
    {
        $this->setPixelId();
        $product = Product::factory()->active()->create([
            'sku' => 'SKU-VC-001',
            'selling_price' => 399.00,
            'stock' => 12,
        ]);

        $html = $this->get(route('product.show', $product))->assertOk()->getContent();

        $this->assertStringContainsString('"content_ids":["SKU-VC-001"]', $html);
        $this->assertStringContainsString('"contents":[{"id":"SKU-VC-001","quantity":1,"item_price":399}]', $html);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // 9. AddToCart uses authoritative product SKU
    // ──────────────────────────────────────────────────────────────────────────
    public function test_add_to_cart_uses_authoritative_product_sku(): void
    {
        $this->setPixelId();
        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'sku' => 'SKU-ATC-001',
            'selling_price' => 275.00,
            'stock' => 10,
        ]);

        $json = $this->actingAs($user, 'web')
            ->postJson(route('cart.add', $product), ['quantity' => 2])
            ->assertOk();

        $this->assertSame('SKU-ATC-001', $json['analytics']['ecommerce']['items'][0]['item_id']);
        $this->assertEqualsWithDelta(275.00, $json['analytics']['ecommerce']['items'][0]['price'], 0.01);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // 10. InitiateCheckout content_ids use product SKUs
    // ──────────────────────────────────────────────────────────────────────────
    public function test_initiate_checkout_content_ids_use_product_skus(): void
    {
        $this->setPixelId();
        $user = User::factory()->create();
        $prodA = Product::factory()->active()->create(['sku' => 'SKU-IC-001', 'selling_price' => 400.00, 'stock' => 10]);
        $prodB = Product::factory()->active()->create(['sku' => 'SKU-IC-002', 'selling_price' => 250.00, 'stock' => 10]);

        $cart = Cart::create(['owner_type' => User::class, 'owner_id' => $user->id]);
        $cart->items()->create(['product_id' => $prodA->id, 'quantity' => 2, 'unit_price' => 400.00, 'mrp' => 400.00, 'gst_rate' => 0]);
        $cart->items()->create(['product_id' => $prodB->id, 'quantity' => 1, 'unit_price' => 250.00, 'mrp' => 250.00, 'gst_rate' => 0]);

        $html = $this->actingAs($user, 'web')->get(route('checkout.index'))->assertOk()->getContent();

        $this->assertStringContainsString('"content_ids":["SKU-IC-001","SKU-IC-002"]', $html);
        $this->assertStringContainsString('"contents":[{"id":"SKU-IC-001","quantity":2,"item_price":400},{"id":"SKU-IC-002","quantity":1,"item_price":250}]', $html);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // 11. OrderItem snapshots product SKU at placement
    // ──────────────────────────────────────────────────────────────────────────
    public function test_order_item_snapshots_product_sku_at_creation(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'sku' => 'SKU-SNP-001',
            'selling_price' => 600.00,
            'mrp' => 600.00,
            'gst_rate' => 0,
            'stock' => 10,
        ]);

        $cart = Cart::create(['owner_type' => User::class, 'owner_id' => $user->id]);
        $cart->items()->create([
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 600.00,
            'mrp' => 600.00,
            'gst_rate' => 0,
        ]);

        $address = [
            'full_name' => 'Test User',
            'mobile' => '9876543210',
            'address_line1' => '42 MG Road',
            'city' => 'Bengaluru',
            'state' => 'Karnataka',
            'pincode' => '560038',
        ];

        $order = app(OrderService::class)->placeOrder($user, $cart, [
            'billing' => $address,
            'shipping' => $address,
            'shipping_method' => 'standard',
            'payment_method' => 'cod',
        ]);

        $item = $order->fresh()->items()->first();
        $this->assertSame('SKU-SNP-001', $item->sku);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // 12. OrderItem SKU is independent of later product SKU change
    // ──────────────────────────────────────────────────────────────────────────
    public function test_order_item_sku_independent_of_later_product_sku_change(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'sku' => 'SKU-SNAP-BEFORE',
            'selling_price' => 300.00,
            'mrp' => 300.00,
            'gst_rate' => 0,
            'stock' => 10,
        ]);

        $cart = Cart::create(['owner_type' => User::class, 'owner_id' => $user->id]);
        $cart->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 300.00,
            'mrp' => 300.00,
            'gst_rate' => 0,
        ]);

        $address = [
            'full_name' => 'Test User',
            'mobile' => '9876543210',
            'address_line1' => '42 MG Road',
            'city' => 'Bengaluru',
            'state' => 'Karnataka',
            'pincode' => '560038',
        ];

        $order = app(OrderService::class)->placeOrder($user, $cart, [
            'billing' => $address,
            'shipping' => $address,
            'shipping_method' => 'standard',
            'payment_method' => 'cod',
        ]);

        $product->update(['sku' => 'SKU-SNAP-AFTER']);

        $item = $order->fresh()->items()->first();
        $this->assertSame('SKU-SNAP-BEFORE', $item->sku, 'OrderItem SKU must be snapshot at creation');
        $this->assertSame('SKU-SNAP-AFTER', $product->fresh()->sku);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // 13. Import skips rows missing SKU
    // ──────────────────────────────────────────────────────────────────────────
    public function test_import_skips_rows_without_sku(): void
    {
        $admin = $this->admin();

        $csv = "name,sku,mrp,selling_price,status\nProduct No SKU,,499,350,active\nProduct With SKU,SKU-IMP-001,599,420,active\n";
        $file = UploadedFile::fake()->createWithContent('products.csv', $csv);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.products.import'), ['import_file' => $file])
            ->assertRedirect();

        $this->assertDatabaseCount('products', 1);
        $this->assertDatabaseHas('products', ['sku' => 'SKU-IMP-001']);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // 14. Import normalizes SKU and upserts
    // ──────────────────────────────────────────────────────────────────────────
    public function test_import_normalizes_sku_and_upserts(): void
    {
        $admin = $this->admin();

        $csv1 = "name,sku,mrp,selling_price,status\nImported Product,  vr-imp-001 ,499,350,active\n";
        $file1 = UploadedFile::fake()->createWithContent('import1.csv', $csv1);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.products.import'), ['import_file' => $file1])
            ->assertRedirect();

        $this->assertDatabaseCount('products', 1);
        $this->assertDatabaseHas('products', ['sku' => 'VR-IMP-001']);

        // Re-import with same normalized SKU should update, not duplicate
        $csv2 = "name,sku,mrp,selling_price,status\nImported Product Updated, vr-imp-001 ,699,490,active\n";
        $file2 = UploadedFile::fake()->createWithContent('import2.csv', $csv2);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.products.import'), ['import_file' => $file2])
            ->assertRedirect();

        $this->assertDatabaseCount('products', 1);
        $this->assertDatabaseHas('products', ['sku' => 'VR-IMP-001', 'mrp' => 699]);
    }
}
