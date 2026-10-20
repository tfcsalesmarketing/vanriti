<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductVideoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
    }

    private function productWithImages(int $imageCount = 2): Product
    {
        $product = Product::factory()->active()->create(['name' => 'Video Demo Product']);

        for ($i = 0; $i < $imageCount; $i++) {
            ProductImage::create([
                'product_id' => $product->id,
                'image_path' => 'products/demo-'.$i.'.jpg',
                'alt_text' => 'Demo '.$i,
                'type' => $i === 0 ? 'main' : 'gallery',
                'sort_order' => $i,
            ]);
        }

        return $product->fresh();
    }

    public function test_product_with_external_video_renders_player_as_second_gallery_item(): void
    {
        $product = $this->productWithImages(2);
        $product->update(['video_url' => 'https://cdn.example.com/videos/demo.mp4']);

        $html = $this->get(route('product.show', $product))->assertOk()->getContent();

        $this->assertStringContainsString('id="vrStageVideo"', $html);
        $this->assertStringContainsString('id="vrPdpVideo"', $html);
        $this->assertStringContainsString('https://cdn.example.com/videos/demo.mp4', $html);
        $this->assertStringContainsString('vr-gallery-thumb-video', $html);
        $this->assertStringContainsString('vendor/plyr/plyr.min.js', $html);
        $this->assertStringContainsString('vendor/plyr/plyr.css', $html);
    }

    public function test_youtube_link_uses_embed_provider_not_video_tag(): void
    {
        $product = $this->productWithImages(2);
        $product->update(['video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ']);

        $html = $this->get(route('product.show', $product))->assertOk()->getContent();

        $this->assertStringContainsString('data-plyr-provider="youtube"', $html);
        $this->assertStringContainsString('data-plyr-embed-id="dQw4w9WgXcQ"', $html);
    }

    public function test_product_without_video_has_no_player(): void
    {
        $product = $this->productWithImages(2);

        $html = $this->get(route('product.show', $product))->assertOk()->getContent();

        $this->assertStringNotContainsString('id="vrPdpVideo"', $html);
        $this->assertStringNotContainsString('vr-gallery-thumb-video', $html);
    }

    public function test_video_only_product_shows_player_without_thumbnails(): void
    {
        $product = Product::factory()->active()->create(['name' => 'Video Only']);
        $product->update(['video_url' => 'https://cdn.example.com/videos/solo.mp4']);

        $html = $this->get(route('product.show', $product))->assertOk()->getContent();

        $this->assertStringContainsString('id="vrStageVideo"', $html);
        $this->assertStringContainsString('id="vrPdpVideo"', $html);
        $this->assertStringContainsString('vr-gallery-thumbs-vertical', $html);
        $this->assertMatchesRegularExpression('/id="vrMainImageWrap" class="[^"]*d-none/', $html);
        $this->assertMatchesRegularExpression('/id="vrStageVideo" class="vr-pdp-video(?! d-none)/', $html);
    }

    public function test_product_video_model_helpers(): void
    {
        $product = new Product(['video_url' => 'https://youtu.be/abc123XYZ_']);
        $this->assertSame('https://youtu.be/abc123XYZ_', $product->videoSrc());
        $this->assertSame('youtube', $product->videoProvider());
        $this->assertSame('abc123XYZ_', $product->videoEmbedId());
        $this->assertFalse($product->hasVideoFile());

        $product->video_url = 'https://vimeo.com/123456789';
        $this->assertSame('vimeo', $product->videoProvider());
        $this->assertSame('123456789', $product->videoEmbedId());

        $product->video_url = 'products/videos/clip.mp4';
        $this->assertNull($product->videoProvider());
        $this->assertTrue($product->hasVideoFile());

        $product->video_url = null;
        $this->assertNull($product->videoSrc());
    }

    public function test_admin_can_store_product_with_video_url(): void
    {
        $admin = Admin::factory()->superAdmin()->create();
        $category = Category::factory()->create();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.products.store'), [
                'name' => 'Video Product',
                'sku' => 'VID-001',
                'status' => 'active',
                'mrp' => 500,
                'selling_price' => 400,
                'category_ids' => [$category->id],
                'primary_category_id' => $category->id,
                'video_url' => 'https://cdn.example.com/videos/demo.mp4',
            ])
            ->assertRedirect(route('admin.products.index'));

        $this->assertDatabaseHas('products', [
            'sku' => 'VID-001',
            'video_url' => 'https://cdn.example.com/videos/demo.mp4',
        ]);
    }

    public function test_admin_update_can_clear_video(): void
    {
        $admin = Admin::factory()->superAdmin()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->active()->create([
            'video_url' => 'https://cdn.example.com/videos/old.mp4',
        ]);
        $product->categories()->attach($category->id, ['is_primary' => true]);

        $this->actingAs($admin, 'admin')
            ->put(route('admin.products.update', $product->slug), [
                'name' => $product->name,
                'sku' => $product->sku,
                'status' => 'active',
                'mrp' => $product->mrp,
                'selling_price' => $product->selling_price,
                'category_ids' => [$category->id],
                'primary_category_id' => $category->id,
                'remove_video' => '1',
            ])
            ->assertRedirect(route('admin.products.index'));

        $this->assertNull($product->fresh()->video_url);
    }

    public function test_admin_edit_page_renders_video_card(): void
    {
        $admin = Admin::factory()->superAdmin()->create();
        $product = $this->productWithImages(2);
        $product->update(['video_url' => 'https://cdn.example.com/videos/demo.mp4']);

        $html = $this->actingAs($admin, 'admin')
            ->get(route('admin.products.edit', $product->slug))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Product Video', $html);
        $this->assertStringContainsString('name="video_url"', $html);
        $this->assertStringContainsString('name="video"', $html);
    }
}
