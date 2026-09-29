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

    public function test_a_single_trigger_button_opens_the_modal(): void
    {
        $product = Product::factory()->active()->create();

        $html = $this->get(route('product.show', $product))->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, 'data-bs-target="#vrShareModal"'));
        $this->assertSame(1, substr_count($html, 'class="vr-share-btn vr-share-trigger"'));
        $this->assertStringContainsString('aria-label="Share '.$product->name.'"', $html);

        // The old inline row of icons must be gone.
        $this->assertStringNotContainsString('vr-share-list', $html);
        $this->assertStringNotContainsString('vr-share-label', $html);
    }

    public function test_the_share_modal_is_built_for_accessibility(): void
    {
        $product = Product::factory()->active()->create();

        $html = $this->get(route('product.show', $product))->assertOk()->getContent();

        $this->assertStringContainsString('id="vrShareModal"', $html);
        $this->assertStringContainsString('role="dialog"', $html);
        $this->assertStringContainsString('aria-modal="true"', $html);
        $this->assertStringContainsString('aria-labelledby="vrShareModalTitle"', $html);
        $this->assertStringContainsString('id="vrShareModalTitle"', $html);
        $this->assertStringContainsString('modal-dialog modal-dialog-centered', $html);
        $this->assertStringContainsString('class="btn-close" data-bs-dismiss="modal"', $html);
    }

    public function test_every_share_option_is_a_labelled_tile_in_the_modal(): void
    {
        $product = Product::factory()->active()->create();

        $html = $this->get(route('product.show', $product))->assertOk()->getContent();

        foreach (['WhatsApp', 'Facebook', 'X', 'LinkedIn', 'Telegram', 'Pinterest', 'Email', 'Instagram', 'Threads', 'Messenger', 'Copy link'] as $label) {
            $this->assertStringContainsString('<span class="vr-share-tile-label">'.$label.'</span>', $html);
        }

        // 10 networks + copy link + the hidden native sheet.
        $this->assertSame(12, substr_count($html, 'class="vr-share-tile-icon"'));
    }

    public function test_the_modal_nests_inside_the_share_root_so_click_delegation_works(): void
    {
        $product = Product::factory()->active()->create();

        $html = $this->get(route('product.show', $product))->assertOk()->getContent();

        $root = strpos($html, 'id="vrProductShare"');
        $modal = strpos($html, 'id="vrShareModal"');

        $this->assertNotFalse($root);
        $this->assertNotFalse($modal);
        $this->assertGreaterThan($root, $modal, 'The share modal must render inside #vrProductShare.');

        // No closing div between the two, so the modal is a true descendant:
        // the script binds click/status/native lookups on that root.
        $this->assertStringNotContainsString('</div>', substr($html, $root, $modal - $root));
    }
}
