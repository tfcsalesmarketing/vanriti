<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesFulfilmentData;
use Tests\TestCase;

class AdminOrderTabsTest extends TestCase
{
    use RefreshDatabase, CreatesFulfilmentData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function actingAdmin(): void
    {
        $this->actingAs(Admin::factory()->superAdmin()->create(), 'admin');
    }

    public function test_index_uses_pending_tab_by_default(): void
    {
        $this->actingAdmin();

        $order = $this->createOrder();

        $this->get(route('admin.orders.index'))
            ->assertOk()
            ->assertSee($order->order_number)
            ->assertSee('Pending Orders');
    }

    public function test_pending_tab_excludes_pushed_orders(): void
    {
        $this->actingAdmin();

        $unpushed = $this->createOrder();
        $pushed = $this->createOrder();
        $this->createShipment($pushed);

        $this->get(route('admin.orders.index', ['tab' => 'pending']))
            ->assertOk()
            ->assertSee($unpushed->order_number)
            ->assertDontSee($pushed->order_number);
    }

    public function test_ready_to_ship_tab_shows_courier_assigned_orders_only(): void
    {
        $this->actingAdmin();

        $withAwb = $this->createOrder();
        $this->createShipment($withAwb, ['awb_number' => 'AWB111111', 'status' => 'packed']);

        $noAwb = $this->createOrder();
        $this->createShipment($noAwb);

        $alreadyShipped = $this->createOrder();
        $this->createShipment($alreadyShipped, ['awb_number' => 'AWB222222', 'status' => 'shipped']);

        $unpushed = $this->createOrder();

        $this->get(route('admin.orders.index', ['tab' => 'ready_to_ship']))
            ->assertOk()
            ->assertSee($withAwb->order_number)
            ->assertDontSee($noAwb->order_number)
            ->assertDontSee($alreadyShipped->order_number)
            ->assertDontSee($unpushed->order_number);
    }

    public function test_assign_courier_tab_shows_pushed_orders_without_awb_only(): void
    {
        $this->actingAdmin();

        $noAwb = $this->createOrder();
        $this->createShipment($noAwb);

        $withAwb = $this->createOrder();
        $this->createShipment($withAwb, ['awb_number' => 'AWB123456', 'status' => 'packed']);

        $this->get(route('admin.orders.index', ['tab' => 'assign_courier']))
            ->assertOk()
            ->assertSee($noAwb->order_number)
            ->assertDontSee($withAwb->order_number);
    }

    public function test_shipped_delivered_and_cancelled_tabs_filter_by_status(): void
    {
        $this->actingAdmin();

        $shipped = $this->createOrder(['order_status' => 'shipped']);
        $delivered = $this->createOrder(['order_status' => 'delivered']);
        $cancelled = $this->createOrder(['order_status' => 'cancelled']);

        $this->get(route('admin.orders.index', ['tab' => 'shipped']))
            ->assertOk()
            ->assertSee($shipped->order_number)
            ->assertDontSee($delivered->order_number)
            ->assertDontSee($cancelled->order_number);

        $this->get(route('admin.orders.index', ['tab' => 'delivered']))
            ->assertOk()
            ->assertSee($delivered->order_number)
            ->assertDontSee($shipped->order_number)
            ->assertDontSee($cancelled->order_number);

        $this->get(route('admin.orders.index', ['tab' => 'cancelled']))
            ->assertOk()
            ->assertSee($cancelled->order_number)
            ->assertDontSee($shipped->order_number)
            ->assertDontSee($delivered->order_number);
    }

    public function test_tab_counts_are_rendered(): void
    {
        $this->actingAdmin();

        $this->createOrder();

        $this->get(route('admin.orders.index', ['tab' => 'pending']))
            ->assertOk()
            ->assertSee('Pending Orders')
            ->assertSee('Ready to Ship')
            ->assertSee('Assign Courier')
            ->assertSee('Shipped')
            ->assertSee('Cancelled')
            ->assertSee('Delivered');
    }
}