<?php

namespace Tests\Feature;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
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

    public function test_category_page_one_keeps_its_own_canonical(): void
    {
        $category = $this->categoryWithProducts(13);

        $html = $this->get(route('shop.category', $category))->assertOk()->getContent();

        $this->assertStringContainsString('rel="canonical" href="'.route('shop.category', $category).'"', $html);
        $this->assertStringContainsString('name="robots" content="index, follow"', $html);
    }

    public function test_category_page_two_renders_pagination_links_and_its_own_canonical(): void
    {
        $category = $this->categoryWithProducts(13);

        $html = $this->get(route('shop.category', $category).'?page=2')->assertOk()->getContent();

        $this->assertStringContainsString('aria-label="Page navigation"', $html);
        $this->assertStringContainsString('rel="canonical" href="'.route('shop.category', $category).'?page=2"', $html);
        $this->assertStringNotContainsString('rel="canonical" href="'.route('shop.category', $category).'"', $html);
    }

    public function test_blog_index_keeps_its_own_canonical(): void
    {
        $html = $this->get(route('blog.index'))->assertOk()->getContent();

        $this->assertStringContainsString('rel="canonical" href="'.route('blog.index').'"', $html);
    }

    public function test_sitemap_drops_changefreq_priority_and_unreliable_lastmod(): void
    {
        $xml = $this->get(route('sitemap'))->assertOk()->getContent();

        $this->assertStringNotContainsString('<changefreq>', $xml);
        $this->assertStringNotContainsString('<priority>', $xml);

        // Pages with no content timestamp of their own must not invent one.
        foreach (['shop.index', 'blog.index', 'faq.index', 'contact.index'] as $route) {
            $this->assertStringNotContainsString(
                '<lastmod>',
                $this->urlBlock($xml, route($route)),
                "{$route} must not carry a generated lastmod."
            );
        }
    }

    public function test_sitemap_uses_real_product_timestamp(): void
    {
        $product = Product::factory()->active()->create();

        $xml = $this->get(route('sitemap'))->assertOk()->getContent();

        $this->assertStringContainsString(
            '<lastmod>'.$product->updated_at->copy()->startOfSecond()->toAtomString().'</lastmod>',
            $this->urlBlock($xml, route('product.show', $product))
        );
    }

    public function test_product_schema_uses_aggregate_offer_for_differently_priced_variants(): void
    {
        $product = Product::factory()->active()->create(['selling_price' => 100]);

        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'selling_price' => 199,
            'stock' => 5,
            'status' => 'active',
        ]);
        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'selling_price' => 299,
            'stock' => 3,
            'status' => 'active',
        ]);

        $html = $this->get(route('product.show', $product))->assertOk()->getContent();

        $this->assertStringContainsString('"@type":"AggregateOffer"', $html);
        $this->assertStringContainsString('"lowPrice":"199.00"', $html);
        $this->assertStringContainsString('"highPrice":"299.00"', $html);
        $this->assertStringContainsString('"offerCount":2', $html);

        // The base row price must not be advertised as the payable price.
        $this->assertStringNotContainsString('"price":"100.00"', $html);

        // Offer-level commerce data must survive at the Offer level.
        $this->assertStringContainsString('"shippingDetails"', $html);
        $this->assertStringContainsString('"hasMerchantReturnPolicy"', $html);
    }

    public function test_product_schema_keeps_flat_offer_for_single_price_variants(): void
    {
        $product = Product::factory()->active()->create();

        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'selling_price' => 249,
            'stock' => 7,
            'status' => 'active',
        ]);

        $html = $this->get(route('product.show', $product))->assertOk()->getContent();

        $this->assertStringNotContainsString('"@type":"AggregateOffer"', $html);
        $this->assertStringContainsString('"@type":"Offer"', $html);
        $this->assertStringContainsString('"price":"249.00"', $html);
        $this->assertStringContainsString('"availability":"https://schema.org/InStock"', $html);
    }

    public function test_product_schema_ignores_disabled_variant_stock_for_availability(): void
    {
        $product = Product::factory()->active()->create(['stock' => 0]);

        // getAvailableStock() sums the unfiltered variants relation, so this
        // disabled variant would otherwise advertise the product as InStock.
        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'stock' => 25,
            'status' => 'inactive',
        ]);

        $html = $this->get(route('product.show', $product))->assertOk()->getContent();

        $this->assertStringContainsString('"availability":"https://schema.org/OutOfStock"', $html);
        $this->assertStringNotContainsString('"availability":"https://schema.org/InStock"', $html);
    }

    public function test_favicon_is_valid_and_referenced(): void
    {
        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertStringContainsString('<link rel="icon"', $html);

        $favicon = public_path('favicon.ico');
        $this->assertFileExists($favicon);
        $this->assertGreaterThan(0, filesize($favicon), 'favicon.ico must not be empty.');

        $bytes = file_get_contents($favicon);
        $this->assertSame('00000100', bin2hex(substr($bytes, 0, 4)), 'favicon.ico must be a valid ICO container.');
    }

    public function test_homepage_has_exactly_one_organization_entity(): void
    {
        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, '"@type":"Organization"'));

        $this->assertStringContainsString('"@id":"'.route('home').'#organization"', $html);
        $this->assertStringContainsString('"email":"'.setting('store_email').'"', $html);
        $this->assertStringContainsString('"telephone":"'.setting('store_phone').'"', $html);
        $this->assertStringContainsString('"@type":"PostalAddress"', $html);
    }

    public function test_non_homepage_pages_keep_single_organization_entity(): void
    {
        $html = $this->get(route('shop.index'))->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, '"@type":"Organization"'));
        $this->assertStringContainsString('"@id":"'.route('home').'#organization"', $html);
    }

    private function categoryWithProducts(int $count): Category
    {
        $category = Category::factory()->active()->create();

        Product::factory()->count($count)->active()->category($category)->create();

        return $category->refresh();
    }

    private function urlBlock(string $xml, string $loc): string
    {
        $matched = preg_match(
            '#<url>\s*<loc>'.preg_quote($loc, '#').'</loc>.*?</url>#s',
            $xml,
            $matches
        );

        $this->assertSame(1, $matched, "Sitemap is missing a <url> entry for {$loc}.");

        return $matches[0];
    }
}
