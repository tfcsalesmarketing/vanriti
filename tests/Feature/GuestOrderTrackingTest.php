<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class GuestOrderTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected function makeOrder(array $attributes = []): Order
    {
        $user = User::factory()->create();

        return Order::create(array_merge([
            'order_number' => 'VAN-2026-990101',
            'user_id' => $user->id,
            'billing_name' => $user->name,
            'billing_mobile' => '9876543210',
            'billing_address_line1' => '42 MG Road',
            'billing_city' => 'Bengaluru',
            'billing_state' => 'Karnataka',
            'billing_pincode' => '560038',
            'shipping_name' => $user->name,
            'shipping_mobile' => '9876543210',
            'shipping_address_line1' => '42 MG Road',
            'shipping_city' => 'Bengaluru',
            'shipping_state' => 'Karnataka',
            'shipping_pincode' => '560038',
            'subtotal' => 1600,
            'grand_total' => 1600,
            'taxable_amount' => 1355.93,
            'tax_amount' => 244.07,
            'payment_method' => 'cod',
            'payment_status' => 'paid',
            'order_status' => 'confirmed',
        ], $attributes));
    }

    public function test_guest_signed_tracking_link_renders_order_page(): void
    {
        $order = $this->makeOrder();

        $url = $order->guestTrackingUrl();

        $this->get($url)
            ->assertOk()
            ->assertSee('VAN-2026-990101')
            ->assertSee('Confirmed', false);
    }

    public function test_guest_tracking_link_requires_valid_signature(): void
    {
        $order = $this->makeOrder();

        $this->get('/track/VAN-2026-990101?mobile=9876543210')
            ->assertForbidden();
    }

    public function test_guest_tracking_link_rejects_missing_mobile(): void
    {
        $order = $this->makeOrder();

        $url = URL::temporarySignedRoute('track.order', now()->addDay(), [
            'order' => $order->order_number,
        ]);

        $this->get($url)
            ->assertForbidden();
    }
}
