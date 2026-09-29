<?php

namespace Tests\Feature;

use App\Models\Product;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductShareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
    }

    public function test_the_product_page_renders_every_share_target(): void
    {
        $product = Product::factory()->active()->create(['name' => 'VANRITI Amla Hair Oil']);
        $url = route('product.show', $product);

        $html = $this->get($url)->assertOk()->getContent();

        foreach (['whatsapp', 'facebook', 'x', 'linkedin', 'telegram', 'pinterest', 'email', 'instagram', 'threads', 'messenger'] as $network) {
            $this->assertStringContainsString('data-share-network="'.$network.'"', $html);
        }

        $this->assertStringContainsString('https://www.facebook.com/sharer/sharer.php?u='.rawurlencode($url), $html);
        $this->assertStringContainsString('https://t.me/share/url?url='.rawurlencode($url), $html);
        $this->assertStringContainsString('https://www.linkedin.com/sharing/share-offsite/?url='.rawurlencode($url), $html);
        $this->assertStringContainsString('https://twitter.com/intent/tweet?url='.rawurlencode($url), $html);
        $this->assertStringContainsString('https://pinterest.com/pin/create/button/?url='.rawurlencode($url), $html);
        $this->assertStringContainsString('mailto:?subject=', $html);
    }

    public function test_share_links_are_encoded_and_opened_safely(): void
    {
        $name = 'Amla & Bhringraj "Kit" #1';
        $product = Product::factory()->active()->create(['name' => $name]);
        $url = route('product.show', $product);

        $html = $this->get($url)->assertOk()->getContent();

        $this->assertStringContainsString(rawurlencode($name), $html);
        $this->assertStringNotContainsString('https://wa.me/?text=Amla & Bhringraj', $html);
        $this->assertStringContainsString('rel="noopener noreferrer"', $html);
        $this->assertStringContainsString('data-share-url="'.$url.'"', $html);
    }

    public function test_the_copy_and_native_share_controls_are_present(): void
    {
        $product = Product::factory()->active()->create();

        $html = $this->get(route('product.show', $product))->assertOk()->getContent();

        $this->assertStringContainsString('data-share-copy', $html);
        $this->assertStringContainsString('data-share-native', $html);
        $this->assertStringContainsString('data-share-status', $html);
        $this->assertStringContainsString('aria-live="polite"', $html);
        $this->assertStringContainsString('bi-link-45deg', $html);
        $this->assertStringContainsString('navigator.share', $html);
        $this->assertStringContainsString('<script nonce="'.csp_nonce().'">', $html);
    }

    public function test_app_only_targets_open_their_site_and_are_marked_for_the_copy_handler(): void
    {
        $product = Product::factory()->active()->create();

        $html = $this->get(route('product.show', $product))->assertOk()->getContent();

        $this->assertStringContainsString('href="https://www.instagram.com/"', $html);
        $this->assertStringContainsString('href="https://www.threads.net/"', $html);
        $this->assertStringContainsString('href="https://www.messenger.com/"', $html);
        $this->assertSame(3, substr_count($html, 'data-share-href='));
    }
}
