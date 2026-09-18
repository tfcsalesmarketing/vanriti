<?php

namespace Tests\Concerns;

use App\Models\Order;
use App\Models\Setting;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

trait CreatesFulfilmentData
{
    protected function enableShipmojo(bool $webhook = false, string $secret = 'whsec_shipmojo_test'): void
    {
        Setting::updateOrCreate(['key' => 'shipmojo_enabled'], ['value' => '1', 'group' => 'shipmojo']);
        Setting::updateOrCreate(['key' => 'shipmojo_public_key'], ['value' => 'shipmojo_pub_key', 'group' => 'shipmojo']);
        Setting::updateOrCreate(['key' => 'shipmojo_private_key'], ['value' => Crypt::encryptString('shipmojo_priv_key'), 'group' => 'shipmojo']);
        Setting::updateOrCreate(['key' => 'shipmojo_warehouse_id'], ['value' => '11', 'group' => 'shipmojo']);

        if ($webhook) {
            Setting::updateOrCreate(['key' => 'shipmojo_webhook_enabled'], ['value' => '1', 'group' => 'shipmojo']);
            Setting::updateOrCreate(['key' => 'shipmojo_webhook_secret'], ['value' => Crypt::encryptString($secret), 'group' => 'shipmojo']);
        }
    }

    protected function createOrder(array $overrides = []): Order
    {
        $user = User::factory()->create();

        return Order::create(array_merge([
            'order_number' => 'VAN-WH-'.Str::upper(Str::random(8)),
            'user_id' => $user->id,
            'billing_name' => $user->name,
            'billing_mobile' => '9876543210',
            'billing_address_line1' => '42 MG Road',
            'billing_city' => 'Bengaluru',
            'billing_state' => 'Karnataka',
            'billing_pincode' => '560038',
            'billing_country' => 'India',
            'shipping_name' => $user->name,
            'shipping_mobile' => '9876543210',
            'shipping_address_line1' => '42 MG Road',
            'shipping_city' => 'Bengaluru',
            'shipping_state' => 'Karnataka',
            'shipping_pincode' => '560038',
            'shipping_country' => 'India',
            'subtotal' => 1000,
            'tax_amount' => 0,
            'grand_total' => 1000,
            'amount_paid' => 1000,
            'amount_due' => 0,
            'payment_method' => 'razorpay',
            'payment_status' => 'paid',
            'order_status' => 'confirmed',
        ], $overrides));
    }

    protected function createShipment(Order $order, array $overrides = []): Shipment
    {
        return $order->shipments()->create(array_merge([
            'status' => 'pending',
            'shipping_method' => 'standard',
            'shipmojo_order_id' => 'SMOJO-'.Str::upper(Str::random(6)),
            'shipmojo_pushed_at' => now(),
        ], $overrides));
    }
}