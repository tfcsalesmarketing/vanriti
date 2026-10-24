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

class ProductCatalogOverhaulTest extends TestCase
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
            'name' => 'Hair Care',
            'slug' => 'hair-care',
            'status' => 'active',
        ]);

        $this->product = Product::create([
            'name' => 'Vanriti Organic Shampoo',
            'slug' => 'vanriti-organic-shampoo',
            'sku' => 'VAN-SHP-001',
            'status' => 'active',
            'mrp' => 899.00,
            'selling_price' => 699.00,
            'stock' => 25,
            'low_stock_threshold' => 10,
            'is_featured' => false,
        ]);
        $this->product->categories()->attach($this->category->id, ['is_primary' => true]);
    }

    public function test_admin_can_view_products_index_with_kpi_cards(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.products.index'));

        $response->assertStatus(200);
        $response->assertSee('Total Catalogue');
        $response->assertSee('Active in Storefront');
        $response->assertSee('Low Stock Alert');
        $response->assertSee('Out of Stock');
        $response->assertSee('Vanriti Organic Shampoo');
        $response->assertSee('VAN-SHP-001');
    }

    public function test_admin_can_quick_adjust_stock_via_ajax(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->postJson(route('admin.products.stock', $this->product->slug), [
                'stock' => 50,
                'reason' => 'Inventory restocked',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'stock' => 50,
            'is_out_of_stock' => false,
            'is_low_stock' => false,
        ]);

        $this->assertEquals(50, $this->product->fresh()->stock);
    }

    public function test_admin_can_toggle_product_status_via_ajax(): void
    {
        $this->assertEquals('active', $this->product->status);

        $response = $this->actingAs($this->admin, 'admin')
            ->postJson(route('admin.products.toggle-status', $this->product->slug));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'status' => 'inactive',
        ]);

        $this->assertEquals('inactive', $this->product->fresh()->status);
    }

    public function test_admin_can_toggle_featured_status_via_ajax(): void
    {
        $this->assertFalse((bool) $this->product->is_featured);

        $response = $this->actingAs($this->admin, 'admin')
            ->postJson(route('admin.products.toggle-featured', $this->product->slug));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'is_featured' => true,
        ]);

        $this->assertTrue((bool) $this->product->fresh()->is_featured);
    }

    public function test_admin_can_view_product_show_with_whatsapp_order_link(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.products.show', $this->product->slug));

        $response->assertStatus(200);
        $response->assertSee('WhatsApp Order');
        $response->assertSee('Storefront View');
        $response->assertSee('WhatsApp Buy Link');
        $response->assertSee('VAN-SHP-001');
    }

    public function test_admin_can_view_product_create_and_edit_with_rich_text_editors(): void
    {
        // 1. Check Create form
        $createRes = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.products.create'));

        $createRes->assertStatus(200);
        $createRes->assertSee('js-rich-editor');
        $createRes->assertSee('quill.snow.css');
        $createRes->assertSee('quill.js');
        $createRes->assertSee('Formatted Text Editors Enabled');

        // 2. Check Edit form
        $editRes = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.products.edit', $this->product->slug));

        $editRes->assertStatus(200);
        $editRes->assertSee('js-rich-editor');
        $editRes->assertSee('quill.snow.css');
        $editRes->assertSee('quill.js');
        $editRes->assertSee('Formatted Text Editors Enabled');
    }

    public function test_admin_can_save_product_with_rich_formatted_content(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('admin.products.store'), [
                'name' => 'Keshveda Herbal Hair Mask',
                'sku' => 'KESH-MSK-01',
                'status' => 'active',
                'mrp' => 899.00,
                'selling_price' => 749.00,
                'category_ids' => [$this->category->id],
                'primary_category_id' => $this->category->id,
                'benefits' => '<ul><li>Deeply nourishes scalp</li><li>Prevents breakage</li></ul>',
                'warnings' => '<p><strong>Caution:</strong> For external use only. Discontinue if rash occurs.</p>',
                'description' => '<p>An ancient Ayurvedic blend of <em>Bhringraj</em> and <em>Amla</em>.</p>',
            ]);

        $response->assertRedirect();

        $saved = Product::where('sku', 'KESH-MSK-01')->firstOrFail();
        $this->assertStringContainsString('<li>Deeply nourishes scalp</li>', $saved->benefits);
        $this->assertStringContainsString('<strong>Caution:</strong>', $saved->warnings);
        $this->assertStringContainsString('<em>Bhringraj</em>', $saved->description);
    }
}
