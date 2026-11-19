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

class ProductMarketplaceLinksTest extends TestCase
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
            'name' => 'Vanriti Herbal Shampoo',
            'slug' => 'vanriti-herbal-shampoo',
            'sku' => 'VAN-HRB-001',
            'status' => 'active',
            'mrp' => 799.00,
            'selling_price' => 599.00,
            'stock' => 20,
            'low_stock_threshold' => 5,
        ]);
        $this->product->categories()->attach($this->category->id, ['is_primary' => true]);
    }

    public function test_admin_can_update_product_marketplace_links(): void
    {
        $amazonLink = 'https://www.amazon.in/dp/B0EXAMPLE123';
        $flipkartLink = 'https://www.flipkart.com/vanriti-herbal-shampoo/p/itmexample123';

        $response = $this->actingAs($this->admin, 'admin')
            ->put(route('admin.products.update', $this->product), [
                'name' => $this->product->name,
                'sku' => $this->product->sku,
                'status' => 'active',
                'channel' => 'vanriti',
                'mrp' => 799.00,
                'selling_price' => 599.00,
                'category_ids' => [$this->category->id],
                'primary_category_id' => $this->category->id,
                'amazon_url' => $amazonLink,
                'flipkart_url' => $flipkartLink,
            ]);

        $response->assertRedirect(route('admin.products.index'));

        $this->product->refresh();
        $this->assertSame($amazonLink, $this->product->amazon_url);
        $this->assertSame($flipkartLink, $this->product->flipkart_url);
        $this->assertTrue($this->product->hasAmazonLink());
        $this->assertTrue($this->product->hasFlipkartLink());
        $this->assertTrue($this->product->hasMarketplaceLinks());
    }

    public function test_invalid_marketplace_urls_are_rejected(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->put(route('admin.products.update', $this->product), [
                'name' => $this->product->name,
                'sku' => $this->product->sku,
                'status' => 'active',
                'mrp' => 799.00,
                'selling_price' => 599.00,
                'category_ids' => [$this->category->id],
                'amazon_url' => 'not-a-valid-url',
                'flipkart_url' => 'also-invalid',
            ]);

        $response->assertSessionHasErrors(['amazon_url', 'flipkart_url']);
    }

    public function test_storefront_renders_marketplace_buttons_when_links_present(): void
    {
        $this->product->update([
            'amazon_url' => 'https://www.amazon.in/dp/B0EXAMPLE123',
            'flipkart_url' => 'https://www.flipkart.com/vanriti-herbal-shampoo/p/itmexample123',
        ]);

        $response = $this->get(route('product.show', $this->product->slug));

        $response->assertOk();
        $response->assertSee('Also Available On');
        $response->assertSee('Buy on', false);
        $response->assertSee('Amazon', false);
        $response->assertSee('https://www.amazon.in/dp/B0EXAMPLE123', false);
        $response->assertSee('Flipkart', false);
        $response->assertSee('https://www.flipkart.com/vanriti-herbal-shampoo/p/itmexample123', false);
    }

    public function test_storefront_does_not_render_marketplace_section_when_links_empty(): void
    {
        $this->product->update([
            'amazon_url' => null,
            'flipkart_url' => null,
        ]);

        $response = $this->get(route('product.show', $this->product->slug));

        $response->assertOk();
        $response->assertDontSee('Also Available On');
        $response->assertDontSee('Buy on Amazon');
        $response->assertDontSee('Buy on Flipkart');
    }

    public function test_admin_edit_form_displays_marketplace_inputs(): void
    {
        $this->product->update([
            'amazon_url' => 'https://www.amazon.in/dp/B0EXAMPLE123',
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.products.edit', $this->product->slug));

        $response->assertOk();
        $response->assertSee('Marketplace Purchase Links');
        $response->assertSee('Amazon Product URL');
        $response->assertSee('Flipkart Product URL');
        $response->assertSee('https://www.amazon.in/dp/B0EXAMPLE123');
    }
}
