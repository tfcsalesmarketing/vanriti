<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TfcNoVanritiLeakTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        while (ob_get_level() > 1) {
            @ob_end_clean();
        }

        parent::tearDown();
    }
    public function test_tfc_home_contains_no_vanriti_reference_and_no_vanriti_products(): void
    {
        // Given an active Vanriti product exists
        $vanritiProduct = Product::create([
            'name' => 'Vanriti Herbal Face Wash',
            'sku' => 'VAN-TEST-001',
            'status' => 'active',
            'channel' => 'vanriti',
            'mrp' => 499.00,
            'selling_price' => 399.00,
            'stock' => 50,
        ]);

        $response = $this->get('/tfc');
        $response->assertStatus(200);

        // Ensure Vanriti product name does NOT appear on TFC home
        $response->assertDontSee('Vanriti Herbal Face Wash');
        $response->assertDontSee('VAN-TEST-001');

        // Ensure no vanriti mentions or vanriti.com store links
        $content = $response->getContent();
        $this->assertStringNotContainsStringIgnoringCase('vanriti', $content);
        $this->assertStringNotContainsString('vanriti.com', $content);

        // Ensure updating banner / coming soon message is rendered
        $response->assertSee('TFC Product Line Updating');
    }

    public function test_tfc_products_index_shows_zero_vanriti_products(): void
    {
        $vanritiProduct = Product::create([
            'name' => 'Vanriti Rose Water Mist',
            'sku' => 'VAN-TEST-002',
            'status' => 'active',
            'channel' => 'vanriti',
            'mrp' => 299.00,
            'selling_price' => 199.00,
            'stock' => 10,
        ]);

        $response = $this->get('/tfc/products');
        $response->assertStatus(200);

        $response->assertDontSee('Vanriti Rose Water Mist');
        $response->assertDontSee('VAN-TEST-002');

        $content = $response->getContent();
        $this->assertStringNotContainsStringIgnoringCase('vanriti', $content);

        // Updating catalogue notice should be present
        $response->assertSee('TFC Product Catalogue Updating');
    }

    public function test_tfc_single_product_returns_404_for_vanriti_product_slug(): void
    {
        $vanritiProduct = Product::create([
            'name' => 'Vanriti Kumkumadi Tailam',
            'sku' => 'VAN-TEST-003',
            'status' => 'active',
            'channel' => 'vanriti',
            'mrp' => 999.00,
            'selling_price' => 799.00,
            'stock' => 10,
        ]);

        // Attempting to view Vanriti product on TFC must 404
        $response = $this->get('/tfc/products/' . $vanritiProduct->slug);
        $response->assertStatus(404);
    }

    public function test_tfc_direct_selling_showcase_does_not_render_vanriti_products(): void
    {
        $vanritiProduct = Product::create([
            'name' => 'Vanriti Bhringraj Oil',
            'sku' => 'VAN-TEST-004',
            'status' => 'active',
            'channel' => 'vanriti',
            'mrp' => 599.00,
            'selling_price' => 499.00,
            'stock' => 20,
        ]);

        $response = $this->get('/tfc/direct-selling');
        $response->assertStatus(200);

        $response->assertDontSee('Vanriti Bhringraj Oil');
        $content = $response->getContent();
        $this->assertStringNotContainsStringIgnoringCase('vanriti', $content);
    }

    public function test_tfc_member_ajax_validation_does_not_leak_vanriti_in_messages(): void
    {
        $user = User::factory()->create([
            'name' => 'Rajesh Sharma',
            'phone' => '9876500001',
            'email' => 'rajesh.sharma@example.com',
        ]);

        // Test mobile check AJAX
        $mobileRes = $this->postJson('/tfc/member/ajax/check-mobile', [
            'mobile' => '9876500001',
        ]);
        $mobileRes->assertStatus(200);
        $mobileContent = $mobileRes->getContent();
        $this->assertStringNotContainsStringIgnoringCase('vanriti', $mobileContent);
        $mobileRes->assertJsonFragment([
            'valid' => true,
            'status' => 'existing_client',
        ]);

        // Test email check AJAX
        $emailRes = $this->postJson('/tfc/member/ajax/check-login', [
            'login' => 'rajesh.sharma@example.com',
        ]);
        $emailContent = $emailRes->getContent();
        $this->assertStringNotContainsStringIgnoringCase('vanriti', $emailContent);
    }

    public function test_vanriti_storefront_still_works_and_displays_vanriti_products(): void
    {
        $vanritiProduct = Product::create([
            'name' => 'Vanriti Gold Radiance Cream',
            'sku' => 'VAN-TEST-005',
            'status' => 'active',
            'channel' => 'vanriti',
            'is_featured' => true,
            'mrp' => 899.00,
            'selling_price' => 699.00,
            'stock' => 15,
        ]);

        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('Vanriti Gold Radiance Cream');

        $pdpResponse = $this->get('/products/' . $vanritiProduct->slug);
        $pdpResponse->assertStatus(200);
        $pdpResponse->assertSee('Vanriti Gold Radiance Cream');
    }

    public function test_tfc_product_is_not_visible_on_vanriti_storefront(): void
    {
        $tfcProduct = Product::create([
            'name' => 'TFC Exclusive Wellness Pack',
            'sku' => 'TFC-TEST-001',
            'status' => 'active',
            'channel' => 'tfc',
            'is_featured' => true,
            'mrp' => 1499.00,
            'selling_price' => 1199.00,
            'stock' => 30,
        ]);

        // Vanriti storefront home should NOT show TFC product
        $homeRes = $this->get('/');
        $homeRes->assertStatus(200);
        $homeRes->assertDontSee('TFC Exclusive Wellness Pack');

        // Vanriti shop should NOT show TFC product
        $shopRes = $this->get('/shop');
        $shopRes->assertStatus(200);
        $shopRes->assertDontSee('TFC Exclusive Wellness Pack');

        // Direct PDP on Vanriti storefront must 404
        $pdpRes = $this->get('/products/' . $tfcProduct->slug);
        $pdpRes->assertStatus(404);

        // But TFC product DOES show on TFC!
        $tfcRes = $this->get('/tfc/products/' . $tfcProduct->slug);
        $tfcRes->assertStatus(200);
        $tfcRes->assertSee('TFC Exclusive Wellness Pack');
    }
}
