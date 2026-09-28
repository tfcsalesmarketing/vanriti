<?php

namespace Tests\Feature;

use App\Models\Banner;
use App\Models\Product;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontSeoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
    }

    public function test_home_always_has_one_h1_including_when_banners_exist(): void
    {
        Banner::query()->create([
            'image' => 'images/logo.png',
            'type' => 'hero',
            'position' => 'home_top',
            'sort_order' => 1,
            'status' => 'active',
        ]);

        $html = $this->get(route('home').'?utm_source=newsletter')->assertOk()->getContent();

        $this->assertSame(1, preg_match_all('/<h1\b/i', $html));
        $this->assertStringContainsString('rel="canonical" href="'.route('home').'"', $html);
        $this->assertStringNotContainsString('utm_source', $html);
        $this->assertStringContainsString('"@type":"Organization"', $html);
    }

    public function test_product_uses_meta_title_and_product_schema(): void
    {
        $product = Product::factory()->active()->create([
            'name' => 'Amla Hair Powder',
            'meta_title' => 'Buy Amla Hair Powder Online',
            'meta_description' => 'Pure amla powder for hair masks.',
            'sku' => 'VNRTSEO1',
        ]);

        $html = $this->get(route('product.show', $product))->assertOk()->getContent();

        $this->assertStringContainsString('<title>Buy Amla Hair Powder Online', $html);
        $this->assertStringContainsString('name="description" content="Pure amla powder for hair masks."', $html);
        $this->assertStringContainsString('"@type":"Product"', $html);
        $this->assertStringContainsString('"hasMerchantReturnPolicy"', $html);
        $this->assertStringContainsString('property="og:type" content="product"', $html);
        $this->assertStringContainsString('rel="canonical" href="'.route('product.show', $product).'"', $html);
    }

    public function test_shop_search_is_noindex_and_canonicalizes_to_shop(): void
    {
        $html = $this->get(route('shop.index', ['q' => 'amla']))->assertOk()->getContent();

        $this->assertStringContainsString('name="robots" content="noindex, follow"', $html);
        $this->assertStringContainsString('rel="canonical" href="'.route('shop.index').'"', $html);
    }

    public function test_login_modal_does_not_use_document_headings(): void
    {
        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertStringNotContainsString('<h3>VANRITI</h3>', $html);
        $this->assertStringNotContainsString('<h4 class="vr-login-heading"', $html);
        $this->assertStringContainsString('id="vrLoginModalTitle"', $html);
        $this->assertStringContainsString('<p class="vr-login-heading"', $html);
    }

    public function test_sitemap_omits_private_urls(): void
    {
        $xml = $this->get(route('sitemap'))->assertOk()->getContent();

        $this->assertStringContainsString(route('home'), $xml);
        $this->assertStringNotContainsString('/track', $xml);
        $this->assertStringNotContainsString('/cart', $xml);
        $this->assertStringNotContainsString('/checkout', $xml);
    }
}
