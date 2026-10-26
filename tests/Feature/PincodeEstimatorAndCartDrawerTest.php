<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PincodeEstimatorAndCartDrawerTest extends TestCase
{
    use RefreshDatabase;

    public function test_pincode_check_returns_serviceability_and_delivery_estimation(): void
    {
        Http::fake([
            'api.postalpincode.in/*' => Http::response([
                [
                    'Status' => 'Success',
                    'PostOffice' => [
                        ['District' => 'New Delhi', 'State' => 'Delhi'],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->postJson(route('pincode.check'), [
            'pincode' => '110001',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'serviceable' => true,
            'pincode' => '110001',
            'city' => 'New Delhi',
            'state' => 'Delhi',
            'cod_available' => true,
        ]);
        $response->assertJsonStructure([
            'estimated_delivery',
            'dispatch_note',
            'free_shipping_threshold',
        ]);
    }

    public function test_pincode_check_rejects_invalid_pincode(): void
    {
        $response = $this->postJson(route('pincode.check'), [
            'pincode' => '123',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => false,
            'serviceable' => false,
        ]);
    }

    public function test_pincode_saved_endpoint_returns_session_pincode(): void
    {
        session(['delivery_pincode' => '560001']);

        $response = $this->getJson(route('pincode.saved'));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'has_saved' => true,
            'pincode' => '560001',
        ]);
    }

    public function test_cart_drawer_returns_rendered_html_and_counts(): void
    {
        $product = Product::factory()->active()->create(['selling_price' => 799]);
        $user = User::factory()->create();

        // Add to cart
        $this->actingAs($user, 'web')->postJson(route('cart.add', $product), [
            'quantity' => 1,
        ]);

        $response = $this->actingAs($user, 'web')->getJson(route('cart.drawer'));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'count' => 1,
        ]);
        $this->assertStringContainsString($product->name, $response->json('html'));
        $this->assertStringContainsString('Proceed to Checkout', $response->json('html'));
    }

    public function test_add_to_cart_ajax_returns_drawer_html(): void
    {
        $product = Product::factory()->active()->create(['selling_price' => 599]);
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'web')->postJson(route('cart.add', $product), [
            'quantity' => 1,
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'cartCount' => 1,
        ]);
        $this->assertNotEmpty($response->json('drawerHtml'));
        $this->assertStringContainsString($product->name, $response->json('drawerHtml'));
    }
}
