<?php

namespace Tests\Feature;

use App\Models\OrderStatusHistory;
use App\Models\Setting;
use App\Models\ShipmentTrackingEvent;
use App\Notifications\OrderStatusNotification;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\CreatesFulfilmentData;
use Tests\TestCase;

class ShipMojoWebhookTest extends TestCase
{
    use RefreshDatabase, CreatesFulfilmentData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
        Notification::fake();
    }

    public function test_courier_assigned_webhook_persists_awb_and_packs_order(): void
    {
        $this->enableShipmojo(true);

        $order = $this->createOrder(['order_status' => 'confirmed']);
        $shipment = $this->createShipment($order);

        $this->postJson(route('shipmojo.webhook'), [
            'webhook_secret' => 'whsec_shipmojo_test',
            'order_id' => $shipment->shipmojo_order_id,
            'status' => 'courier assigned',
            'awb_number' => 'DLV123456789IN',
            'courier_company' => 'Delhivery',
            'courier_service' => 'D-Plus',
        ])->assertOk()->assertJson(['result' => '1']);

        $shipment->refresh();
        $this->assertSame('DLV123456789IN', $shipment->awb_number);
        $this->assertSame('Delhivery', $shipment->courier);
        $this->assertSame('D-Plus', $shipment->courier_service);
        $this->assertSame('packed', $shipment->status);
        $this->assertSame('packed', $order->fresh()->order_status);

        Notification::assertSentTo($order->user, OrderStatusNotification::class);

        $this->assertSame(1, OrderStatusHistory::where('order_id', $order->id)->where('status', 'packed')->count());
    }

    public function test_delivered_webhook_syncs_order_and_records_scan_events(): void
    {
        $this->enableShipmojo(true);

        $order = $this->createOrder(['order_status' => 'shipped']);
        $shipment = $this->createShipment($order, ['awb_number' => 'DLV999999999IN', 'status' => 'shipped']);

        $this->postJson(route('shipmojo.webhook'), [
            'webhook_secret' => 'whsec_shipmojo_test',
            'order_id' => $shipment->shipmojo_order_id,
            'status' => 'delivered',
            'scan_detail' => [
                [
                    'status' => 'out_for_delivery',
                    'date' => '2026-09-17 09:30:00',
                    'location' => 'Bengaluru Hub',
                    'activity' => 'Package out for delivery',
                ],
                [
                    'status' => 'delivered',
                    'date' => '2026-09-17 16:00:00',
                    'location' => 'Bengaluru',
                    'activity' => 'Delivered to customer',
                ],
            ],
        ])->assertOk()->assertJson(['result' => '1']);

        $shipment->refresh();
        $this->assertSame('delivered', $shipment->status);
        $this->assertNotNull($shipment->delivered_at);
        $this->assertSame('delivered', $order->fresh()->order_status);

        $this->assertSame(2, ShipmentTrackingEvent::where('shipment_id', $shipment->id)->count());
        Notification::assertSentTo($order->user, OrderStatusNotification::class);
    }

    public function test_replayed_webhook_is_idempotent(): void
    {
        $this->enableShipmojo(true);

        $order = $this->createOrder(['order_status' => 'shipped']);
        $shipment = $this->createShipment($order, ['awb_number' => 'DLV777777777IN', 'status' => 'shipped']);

        $payload = [
            'webhook_secret' => 'whsec_shipmojo_test',
            'order_id' => $shipment->shipmojo_order_id,
            'status' => 'delivered',
            'scan_detail' => [
                [
                    'status' => 'delivered',
                    'date' => '2026-09-17 16:00:00',
                    'location' => 'Bengaluru',
                    'activity' => 'Delivered to customer',
                ],
            ],
        ];

        $this->postJson(route('shipmojo.webhook'), $payload)->assertOk();
        $this->postJson(route('shipmojo.webhook'), $payload)->assertOk();

        $this->assertSame(1, ShipmentTrackingEvent::where('shipment_id', $shipment->id)->count());
        Notification::assertSentTo($order->user, OrderStatusNotification::class, 1);
        $this->assertSame(1, OrderStatusHistory::where('order_id', $order->id)->where('status', 'delivered')->count());
    }

    public function test_webhook_rejects_invalid_credentials(): void
    {
        $this->enableShipmojo(true, 'whsec_shipmojo_test');

        $order = $this->createOrder(['order_status' => 'confirmed']);
        $shipment = $this->createShipment($order);

        $this->postJson(route('shipmojo.webhook'), [
            'order_id' => $shipment->shipmojo_order_id,
            'status' => 'delivered',
        ], [
            'x-shipmojo-signature' => 'not-a-valid-hmac',
        ])->assertStatus(401);

        $this->assertSame('confirmed', $order->fresh()->order_status);
        Notification::assertNothingSent();
    }

    public function test_webhook_fails_closed_when_no_secret_configured(): void
    {
        $this->enableShipmojo(false, '');
        Setting::updateOrCreate(['key' => 'shipmojo_webhook_secret'], ['value' => '']);

        $order = $this->createOrder(['order_status' => 'confirmed']);
        $shipment = $this->createShipment($order);

        $this->postJson(route('shipmojo.webhook'), [
            'order_id' => $shipment->shipmojo_order_id,
            'status' => 'delivered',
            'webhook_secret' => 'wrong-secret',
        ])->assertStatus(401);

        $this->assertSame('confirmed', $order->fresh()->order_status);
        $this->assertSame('pending', $shipment->fresh()->status);
        Notification::assertNothingSent();
    }

    public function test_webhook_returns_404_for_unknown_order(): void
    {
        $this->enableShipmojo(true);

        $this->postJson(route('shipmojo.webhook'), [
            'webhook_secret' => 'whsec_shipmojo_test',
            'order_id' => 'SMOJO-DOES-NOT-EXIST',
            'status' => 'delivered',
        ])->assertStatus(404)->assertJson(['result' => '0']);
    }

    public function test_webhook_ignored_when_disabled(): void
    {
        $this->enableShipmojo(true, 'whsec_shipmojo_test');
        Setting::updateOrCreate(['key' => 'shipmojo_webhook_enabled'], ['value' => '0']);

        $order = $this->createOrder(['order_status' => 'confirmed']);
        $shipment = $this->createShipment($order);

        $this->postJson(route('shipmojo.webhook'), [
            'webhook_secret' => 'whsec_shipmojo_test',
            'order_id' => $shipment->shipmojo_order_id,
            'status' => 'delivered',
        ])->assertOk()->assertJson(['message' => 'Webhook disabled']);

        $this->assertSame('confirmed', $order->fresh()->order_status);
        $this->assertSame('pending', $shipment->fresh()->status);
    }

    public function test_webhook_authenticates_via_query_token(): void
    {
        $this->enableShipmojo(true, 'whsec_shipmojo_test');

        $order = $this->createOrder(['order_status' => 'confirmed']);
        $shipment = $this->createShipment($order);

        $this->postJson(route('shipmojo.webhook', ['token' => 'whsec_shipmojo_test']), [
            'order_id' => $shipment->shipmojo_order_id,
            'status' => 'in transit',
            'awb_number' => 'AWB-TOKEN-TEST',
            'courier' => 'Ekart Flat Price',
        ])->assertOk()->assertJson(['result' => '1']);

        $shipment->refresh();
        $this->assertSame('AWB-TOKEN-TEST', $shipment->awb_number);
        $this->assertSame('Ekart Flat Price', $shipment->courier);
        $this->assertSame('shipped', $shipment->status);
        $this->assertSame('shipped', $order->fresh()->order_status);
        $this->assertSame('https://shipmojo.com/track/AWB-TOKEN-TEST', $order->fresh()->tracking_url);
    }

    public function test_webhook_handles_misspelled_refrence_id_and_nested_shipping_details(): void
    {
        $this->enableShipmojo(true, 'whsec_shipmojo_test');

        $order = $this->createOrder(['order_status' => 'confirmed']);
        $shipment = $this->createShipment($order, ['shipmojo_reference_id' => $order->order_number]);

        $this->postJson(route('shipmojo.webhook', ['token' => 'whsec_shipmojo_test']), [
            'refrence_id' => $order->order_number, // ShipMojo misspelled key
            'current_status' => 'in transit',
            'shipping_details' => [
                'awb_number' => 'AWB-NESTED-999',
                'courier_company' => 'Delhivery',
            ],
        ])->assertOk()->assertJson(['result' => '1']);

        $shipment->refresh();
        $this->assertSame('AWB-NESTED-999', $shipment->awb_number);
        $this->assertSame('Delhivery', $shipment->courier);
        $this->assertSame('shipped', $shipment->status);
    }
}