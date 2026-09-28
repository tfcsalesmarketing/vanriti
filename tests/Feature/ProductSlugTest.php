<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductSlugRedirect;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProductSlugTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
    }

    public function test_admin_create_uses_product_name_slug_without_id_or_sku_prefix(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = Admin::factory()->superAdmin()->create();
        $category = Category::factory()->create();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.products.store'), [
                'name' => 'VANRITI Amla Hair Oil',
                'sku' => 'VNRT099',
                'status' => 'active',
                'mrp' => 500,
                'selling_price' => 350,
                'category_ids' => [$category->id],
            ])
            ->assertRedirect(route('admin.products.index'));

        $product = Product::query()->where('sku', 'VNRT099')->first();

        $this->assertNotNull($product);
        $this->assertSame(Str::slug('VANRITI Amla Hair Oil'), $product->slug);
        $this->assertStringNotContainsString((string) $product->id.'-', $product->slug);
        $this->assertFalse(str_starts_with($product->slug, '099-'));
    }

    public function test_duplicate_names_get_numeric_suffix_not_an_id_prefix(): void
    {
        Product::factory()->create(['name' => 'VANRITI Same Oil']);
        $second = Product::factory()->create(['name' => 'VANRITI Same Oil']);

        $this->assertSame(Str::slug('VANRITI Same Oil'), Product::query()->orderBy('id')->first()->slug);
        $this->assertSame(Str::slug('VANRITI Same Oil').'-2', $second->slug);
    }

    public function test_existing_prefixed_slugs_are_rewritten_and_old_urls_redirect(): void
    {
        $product = Product::factory()->active()->create([
            'name' => 'VANRITI Amla and Bhringraj Hair Care Kit',
        ]);

        $product->slug = '001-amla-bhringraj-hair-care-kit';
        $product->saveQuietly();

        $this->artisan('products:normalize-slugs')->assertSuccessful();

        $product->refresh();
        $this->assertSame(Str::slug('VANRITI Amla and Bhringraj Hair Care Kit'), $product->slug);
        $this->assertTrue(
            ProductSlugRedirect::query()
                ->where('product_id', $product->id)
                ->where('slug', '001-amla-bhringraj-hair-care-kit')
                ->exists()
        );

        $this->get('/products/001-amla-bhringraj-hair-care-kit')
            ->assertStatus(301)
            ->assertRedirect(route('product.show', $product));
        $this->get(route('product.show', $product))->assertOk();
    }
}
