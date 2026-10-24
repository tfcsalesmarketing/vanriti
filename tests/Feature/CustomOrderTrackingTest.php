<?php

namespace Tests\Feature;

use App\Models\OrderItem;
use App\Models\Shipment;
use App\Models\ShipmentTrackingEvent;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesFulfilmentData;
use Tests\TestCase;

class CustomOrderTrackingTest extends TestCase
{
    use RefreshDatabase, CreatesFulfilmentData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
    }

    public function test_track_page_loads_successfully(): void
    {
        $response = $this->get(route('track'));
        $response->assertOk();
        $response->assertSee('Track Your Order');
        $response->assertSee('Order # or AWB Number');
    }

    public function test_customer_can_track_order_with_order_number_and_mobile(): void
    {
        $order = $this->createOrder([
            'order_number' => 'VAN-2026-TEST01',
            'shipping_mobile' => '9876543210',
            'order_status' => 'shipped',
            'grand_total' => 4500,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_name' => 'Rose Silk Banarasi Dupatta',
            'quantity' => 1,
            'unit_price' => 4500,
            'mrp' => 4500,
            'taxable_amount' => 4285.71,
            'gst_rate' => 5,
        ]);

        $shipment = Shipment::create([
            'order_id' => $order->id,
            'courier' => 'Delhivery Express',
            'awb_number' => 'DEL1234567890',
            'status' => 'shipped',
            'estimated_delivery' => now()->addDays(2)->toDateString(),
        ]);

        ShipmentTrackingEvent::create([
            'shipment_id' => $shipment->id,
            'status' => 'in_transit',
            'description' => 'Bagged and departed Jaipur transit facility',
            'location' => 'Jaipur Sort Center',
            'event_date' => now()->subHours(4),
        ]);

        $response = $this->post(route('track.lookup'), [
            'order_number' => 'VAN-2026-TEST01',
            'mobile' => '9876543210',
        ]);

        $response->assertOk();
        $response->assertSee('VAN-2026-TEST01');
        $response->assertSee('Delhivery Express');
        $response->assertSee('DEL1234567890');
        $response->assertSee('In Transit');
        $response->assertSee('Rose Silk Banarasi Dupatta');
        $response->assertSee('Bagged and departed Jaipur transit facility');
        $response->assertSee('Jaipur Sort Center');
    }

    public function test_customer_can_track_order_using_awb_number(): void
    {
        $order = $this->createOrder([
            'order_number' => 'VAN-2026-AWB01',
            'shipping_mobile' => '9811223344',
            'order_status' => 'shipped',
        ]);

        $shipment = Shipment::create([
            'order_id' => $order->id,
            'courier' => 'Blue Dart Express',
            'awb_number' => 'BLUEDART998877',
            'status' => 'shipped',
        ]);

        $response = $this->post(route('track.lookup'), [
            'order_number' => 'BLUEDART998877', // Lookup by AWB
            'mobile' => '9811223344',
        ]);

        $response->assertOk();
        $response->assertSee('VAN-2026-AWB01');
        $response->assertSee('Blue Dart Express');
        $response->assertSee('BLUEDART998877');
    }

    public function test_signed_tracking_url_renders_custom_branded_page_directly(): void
    {
        $order = $this->createOrder([
            'order_number' => 'VAN-2026-SIGNED',
            'shipping_mobile' => '9877001122',
            'order_status' => 'out_for_delivery',
        ]);

        $signedUrl = $order->trackingUrl();
        $this->assertStringContainsString('/track/VAN-2026-SIGNED', $signedUrl);

        $response = $this->get($signedUrl);
        $response->assertOk();
        $response->assertSee('VAN-2026-SIGNED');
        $response->assertSee('Out for Delivery');
        $response->assertSee('Need Help? Chat on WhatsApp');
    }
}
