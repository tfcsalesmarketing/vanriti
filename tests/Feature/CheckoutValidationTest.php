<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
    }

    protected function addressData(string $paymentMethod = 'cod'): array
    {
        return [
            'shipping_name' => 'Aarav Mehta',
            'shipping_mobile' => '9876543210',
            'shipping_address_line1' => '42 MG Road',
            'shipping_city' => 'Bengaluru',
            'shipping_state' => 'Karnataka',
            'shipping_pincode' => '560038',
            'shipping_country' => 'India',
            'billing_same' => '1',
            'shipping_method' => 'standard',
            'payment_method' => $paymentMethod,
        ];
    }

    public function test_empty_shipping_name_shows_field_error(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'web')
            ->from(route('checkout.index'))
            ->post(route('checkout.store'), array_merge($this->addressData(), ['shipping_name' => '']));

        $response->assertSessionHasErrors('shipping_name');
    }

    public function test_invalid_mobile_is_rejected_on_submit(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'web')
            ->from(route('checkout.index'))
            ->post(route('checkout.store'), array_merge($this->addressData(), [
                'shipping_mobile' => '12345',
            ]))
            ->assertSessionHasErrors('shipping_mobile');
    }

    public function test_valid_mobile_with_prefix_passes_validation(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'selling_price' => 800.00,
            'mrp' => 1000.00,
            'gst_rate' => 18,
            'stock' => 25,
        ]);
        Inventory::create([
            'stockable_type' => Product::class,
            'stockable_id' => $product->id,
            'stock_on_hand' => $product->stock,
            'low_stock_threshold' => $product->low_stock_threshold ?? 5,
        ]);

        $this->actingAs($user, 'web')->post(route('cart.add', $product), ['quantity' => 1]);
        $this->actingAs($user, 'web')->post(route('cart.add', $product), ['quantity' => 1]);

        $this->actingAs($user, 'web')
            ->post(route('checkout.store'), array_merge($this->addressData(), [
                'shipping_mobile' => '+91 98765 43210',
            ]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();
    }

    public function test_billing_fields_required_when_billing_not_same(): void
    {
        $user = User::factory()->create();

        $payload = array_merge($this->addressData(), [
            'billing_same' => '1',
        ]);
        unset($payload['billing_same']);

        $this->actingAs($user, 'web')
            ->from(route('checkout.index'))
            ->post(route('checkout.store'), $payload)
            ->assertSessionHasErrors([
                'billing_name',
                'billing_mobile',
                'billing_address_line1',
                'billing_city',
                'billing_state',
                'billing_pincode',
            ]);
    }

    public function test_invalid_pincode_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'web')
            ->from(route('checkout.index'))
            ->post(route('checkout.store'), array_merge($this->addressData(), [
                'shipping_pincode' => 'abc',
            ]))
            ->assertSessionHasErrors('shipping_pincode');
    }

    public function test_live_validate_endpoint_returns_inline_errors(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'web')
            ->postJson(route('checkout.validate'), ['shipping_mobile' => '12345'])
            ->assertStatus(422)
            ->assertJson(['valid' => false]);

        $this->actingAs($user, 'web')
            ->postJson(route('checkout.validate'), ['shipping_mobile' => '9876543210'])
            ->assertOk()
            ->assertJson(['valid' => true]);

        $this->actingAs($user, 'web')
            ->postJson(route('checkout.validate'), ['shipping_name' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors('shipping_name');
    }
}