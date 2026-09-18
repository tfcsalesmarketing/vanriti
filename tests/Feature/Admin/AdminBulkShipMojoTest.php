<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreatesFulfilmentData;
use Tests\TestCase;

class AdminBulkShipMojoTest extends TestCase
{
    use RefreshDatabase, CreatesFulfilmentData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function actingAdmin(): Admin
    {
        $admin = Admin::factory()->superAdmin()->create();
        $this->actingAs($admin, 'admin');

        return $admin;
    }

    public function test_bulk_push_pushes_orders_and_records_shipments(): void
    {
        $this->enableShipmojo();
        $this->actingAdmin();

        Http::fake([
            'shipping-api.com/app/api/v1/push-order' => Http::response([
                'result' => '1',
                'data' => ['order_id' => 'SMOJO-XXX', 'reference_id' => 'REF-XXX'],
            ]),
        ]);

        $a = $this->createOrder();
        $b = $this->createOrder();

        $this->post(route('admin.orders.shipmojo.bulk.push'), [
            'order_ids' => [$a->id, $b->id],
        ])->assertRedirect()->assertSessionHas('success');

        Http::assertSentCount(2);
        $this->assertTrue($a->shipments()->whereNotNull('shipmojo_pushed_at')->exists());
        $this->assertTrue($b->shipments()->whereNotNull('shipmojo_pushed_at')->exists());
    }

    public function test_bulk_push_stores_shipmojo_id_from_aliased_response(): void
    {
        $this->enableShipmojo();
        $this->actingAdmin();

        Http::fake([
            'shipping-api.com/app/api/v1/push-order' => Http::response([
                'result' => '1',
                'data' => ['orderId' => 'SM-ALIAS-1', 'referenceId' => 'REF-ALIAS-1'],
            ]),
        ]);

        $order = $this->createOrder();

        $this->post(route('admin.orders.shipmojo.bulk.push'), [
            'order_ids' => [$order->id],
        ])->assertRedirect()->assertSessionHas('success');

        $shipment = $order->shipments()->first();
        $this->assertSame('SM-ALIAS-1', $shipment->shipmojo_order_id);
        $this->assertSame('REF-ALIAS-1', $shipment->shipmojo_reference_id);
    }

    public function test_bulk_auto_assign_surfaces_provider_error_detail(): void
    {
        $this->enableShipmojo();
        $this->actingAdmin();

        Http::fake([
            'shipping-api.com/app/api/v1/auto-assign-order' => Http::response([
                'result' => '0',
                'message' => 'Error',
                'data' => ['error_message' => 'Pickup pincode not serviceable.', 'error_code' => 'PINCODE_1'],
            ], 422),
        ]);

        $order = $this->createOrder();
        $this->createShipment($order);

        $this->post(route('admin.orders.shipmojo.bulk.auto-assign'), [
            'order_ids' => [$order->id],
        ])->assertRedirect()->assertSessionHas(
            'error',
            fn (string $message) => str_contains($message, 'Pickup pincode not serviceable.')
        );
    }

    public function test_bulk_push_reports_failures_without_aborting(): void
    {
        $this->enableShipmojo();
        $this->actingAdmin();

        Http::fake([
            'shipping-api.com/app/api/v1/push-order' => Http::response([
                'result' => '0',
                'message' => 'Duplicate order',
            ]),
        ]);

        $order = $this->createOrder();

        $this->post(route('admin.orders.shipmojo.bulk.push'), [
            'order_ids' => [$order->id],
        ])->assertRedirect()->assertSessionHas('error');

        Http::assertSentCount(1);
        $this->assertFalse($order->shipments()->whereNotNull('shipmojo_pushed_at')->exists());
    }

    public function test_bulk_auto_assign_skips_unpushed_orders_and_assigns_awb(): void
    {
        $this->enableShipmojo();
        $this->actingAdmin();

        Http::fake([
            'shipping-api.com/app/api/v1/auto-assign-order' => Http::response([
                'result' => '1',
                'data' => [
                    'awb_number' => 'AWBBULK01',
                    'courier_company' => 'Delhivery',
                    'courier_company_service' => 'D-Plus',
                ],
            ]),
        ]);

        $unpushed = $this->createOrder();
        $pushed = $this->createOrder();
        $this->createShipment($pushed);

        $this->post(route('admin.orders.shipmojo.bulk.auto-assign'), [
            'order_ids' => [$unpushed->id, $pushed->id],
        ])->assertRedirect()->assertSessionHas('warning');

        Http::assertSentCount(1);

        $shipment = $pushed->shipments()->latest()->first();
        $this->assertSame('AWBBULK01', $shipment->awb_number);
        $this->assertSame('Delhivery', $shipment->courier);
        $this->assertSame('packed', $shipment->status);
        $this->assertSame('packed', $pushed->fresh()->order_status);
    }

    public function test_bulk_schedule_pickup_requires_awb_and_marks_order_shipped(): void
    {
        $this->enableShipmojo();
        $this->actingAdmin();

        Http::fake([
            'shipping-api.com/app/api/v1/schedule-pickup' => Http::response([
                'result' => '1',
                'data' => ['awb_number' => 'AWBBULK01', 'lr_number' => 'LR01'],
            ]),
        ]);

        $order = $this->createOrder();
        $shipment = $this->createShipment($order, ['awb_number' => 'AWBBULK01', 'status' => 'packed']);

        $this->post(route('admin.orders.shipmojo.bulk.schedule-pickup'), [
            'order_ids' => [$order->id],
        ])->assertRedirect()->assertSessionHas('success');

        Http::assertSentCount(1);

        $this->assertSame('shipped', $shipment->fresh()->status);
        $this->assertSame('shipped', $order->fresh()->order_status);
    }

    public function test_bulk_labels_downloads_zip_archive(): void
    {
        $this->enableShipmojo();
        $this->actingAdmin();

        Http::fake([
            'shipping-api.com/app/api/v1/get-order-label/*' => Http::response([
                'result' => '1',
                'data' => [['label' => 'data:image/png;base64,'.base64_encode('FAKEPNGDATA')]],
            ]),
        ]);

        $order = $this->createOrder();
        $this->createShipment($order, ['awb_number' => 'AWBLBL01']);

        $response = $this->post(route('admin.orders.shipmojo.bulk.labels'), [
            'order_ids' => [$order->id],
        ]);

        $response->assertOk();
        $this->assertStringContainsString('zip', (string) $response->headers->get('content-type'));
    }

    public function test_bulk_labels_returns_error_when_no_labels_available(): void
    {
        $this->enableShipmojo();
        $this->actingAdmin();

        $order = $this->createOrder();

        $this->post(route('admin.orders.shipmojo.bulk.labels'), [
            'order_ids' => [$order->id],
        ])->assertRedirect()->assertSessionHas('error');
    }

    public function test_bulk_labels_returns_json_error_for_ajax_request(): void
    {
        $this->enableShipmojo();
        $this->actingAdmin();

        $order = $this->createOrder();

        $this->postJson(route('admin.orders.shipmojo.bulk.labels'), [
            'order_ids' => [$order->id],
        ])->assertStatus(422)->assertJson(['success' => false]);
    }
}