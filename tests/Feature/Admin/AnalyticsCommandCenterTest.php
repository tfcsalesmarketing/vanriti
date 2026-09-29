<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\AnalyticsConversion;
use App\Models\AnalyticsEvent;
use App\Models\Category;
use App\Models\DadiConversation;
use App\Models\DadiRecommendationEvent;
use App\Models\Order;
use App\Models\Product;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use App\Services\Analytics\AnalyticsCommandCenterService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class AnalyticsCommandCenterTest extends TestCase
{
    use RefreshDatabase;

    protected AnalyticsCommandCenterService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->seed(SettingsSeeder::class);
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->service = app(AnalyticsCommandCenterService::class);
    }

    protected function manager(): Admin
    {
        $admin = Admin::factory()->create();
        $admin->roles()->attach(Role::where('slug', 'manager')->firstOrFail());

        return $admin;
    }

    protected function makeUser(): User
    {
        return User::factory()->create();
    }

    protected function makeProduct(?string $sku = null, float $price = 100.0): Product
    {
        return Product::factory()->create([
            'sku' => $sku ?? ('TSKU'.Str::upper(Str::random(4))),
            'selling_price' => $price,
            'mrp' => $price,
            'stock' => 50,
            'low_stock_threshold' => 5,
            'status' => 'active',
        ]);
    }

    protected function forceTimestamps(string $table, int $id, Carbon $at): void
    {
        DB::table($table)->where('id', $id)->update([
            'created_at' => $at,
            'updated_at' => $at,
        ]);
    }

    protected function recordEvent(string $type, array $attrs = []): AnalyticsEvent
    {
        return AnalyticsEvent::create(array_merge([
            'event_type' => $type,
            'session_id' => Str::random(64),
            'occurred_at' => now(),
        ], $attrs));
    }

    protected function ledgerRow(string $state, ?Carbon $at = null): void
    {
        $at = $at ?? Carbon::now();

        $row = AnalyticsConversion::create([
            'event_type' => 'purchase',
            'order_number' => 'VAN-CAPI-'.Str::random(6),
            'channel' => 'meta',
            'payload' => [],
            'meta_state' => $state,
            'meta_sent_at' => $state === 'sent' ? $at : null,
            'meta_attempts' => 1,
            'created_at' => $at,
            'updated_at' => $at,
        ]);

        $this->forceTimestamps('analytics_conversions', $row->id, $at);
    }

    protected function createOrder(
        User $user,
        float $grandTotal,
        string $paymentMethod = 'cod',
        string $paymentStatus = 'pending',
        string $orderStatus = 'pending',
        ?Carbon $createdAt = null,
        float $couponDiscount = 0.0,
        ?string $orderNumber = null
    ): Order {
        $createdAt = $createdAt ?? Carbon::now();

        $order = Order::create([
            'order_number' => $orderNumber ?? 'VAN-TEST-'.Str::random(6),
            'user_id' => $user->id,
            'billing_name' => 'Analytics Test',
            'billing_mobile' => '9876543210',
            'billing_address_line1' => '42 MG Road',
            'billing_city' => 'Bengaluru',
            'billing_state' => 'Karnataka',
            'billing_pincode' => '560038',
            'shipping_name' => 'Analytics Test',
            'shipping_mobile' => '9876543210',
            'shipping_address_line1' => '42 MG Road',
            'shipping_city' => 'Bengaluru',
            'shipping_state' => 'Karnataka',
            'shipping_pincode' => '560038',
            'subtotal' => $grandTotal,
            'grand_total' => $grandTotal,
            'amount_due' => $paymentMethod === 'cod' ? $grandTotal : 0,
            'amount_paid' => $paymentMethod === 'cod' ? 0 : $grandTotal,
            'payment_method' => $paymentMethod,
            'payment_status' => $paymentStatus,
            'order_status' => $orderStatus,
            'coupon_discount' => $couponDiscount,
        ]);

        $this->forceTimestamps('orders', $order->id, $createdAt);

        return $order->refresh();
    }

    protected function addItem(Order $order, Product $product, int $qty = 1, ?float $unit = null): void
    {
        $unit = $unit ?? $product->selling_price;

        $item = $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'sku' => $product->sku,
            'quantity' => $qty,
            'mrp' => $unit,
            'unit_price' => $unit,
            'gst_rate' => 0,
            'tax_amount' => 0,
            'total_price' => $unit * $qty,
        ]);

        $this->forceTimestamps('order_items', $item->id, $order->created_at);
    }

    protected function dadiEvent(Product $product, string $action, ?string $orderNumber = null, ?Carbon $createdAt = null): DadiRecommendationEvent
    {
        $at = $createdAt ?? Carbon::now();

        $event = DadiRecommendationEvent::create([
            'reference' => Str::random(26),
            'product_id' => $product->id,
            'action' => $action,
            'order_number' => $orderNumber,
        ]);

        $this->forceTimestamps('dadi_recommendation_events', $event->id, $at);

        return $event->refresh();
    }

    protected function range(): array
    {
        $from = Carbon::now()->subDays(3)->toDateString();
        $to = Carbon::now()->subDays(1)->toDateString();

        return [$from, $to];
    }

    /*
    |--------------------------------------------------------------------------
    | Authorization
    |--------------------------------------------------------------------------
    */

    public function test_guest_is_redirected_to_admin_login(): void
    {
        $this->get(route('admin.analytics'))->assertRedirect(route('admin.login'));
    }

    public function test_super_admin_can_access_the_command_center(): void
    {
        $admin = Admin::factory()->superAdmin()->create();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.analytics'))
            ->assertOk()
            ->assertSee('Analytics Command Center');
    }

    public function test_admin_without_view_reports_permission_gets_403(): void
    {
        $admin = Admin::factory()->create(['is_super_admin' => false]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.analytics'))
            ->assertForbidden();
    }

    public function test_manager_with_view_reports_can_access(): void
    {
        $this->actingAs($this->manager(), 'admin')
            ->get(route('admin.analytics'))
            ->assertOk();
    }

    /*
    |--------------------------------------------------------------------------
    | Date filtering
    |--------------------------------------------------------------------------
    */

    public function test_default_range_is_last_30_days_and_excludes_older_orders(): void
    {
        $user = $this->makeUser();
        $this->createOrder($user, 500.00, 'cod', 'pending', 'pending', Carbon::now());
        $this->createOrder($user, 900.00, 'cod', 'pending', 'pending', Carbon::now()->subDays(40));

        $this->actingAs($this->manager(), 'admin')
            ->get(route('admin.analytics'))
            ->assertOk()
            ->assertSee('₹ 500.00')
            ->assertDontSee('₹ 900.00');
    }

    public function test_custom_range_respects_both_boundaries(): void
    {
        [$from, $to] = $this->range();

        $user = $this->makeUser();
        $this->createOrder($user, 100.00, 'cod', 'pending', 'pending', Carbon::parse($to.' 12:00:00'));
        $this->createOrder($user, 200.00, 'cod', 'pending', 'pending', Carbon::parse($from.' 12:00:00'));
        $this->createOrder($user, 400.00, 'cod', 'pending', 'pending', Carbon::parse($from.' 12:00:00')->subDays(2));

        $this->actingAs($this->manager(), 'admin')
            ->get(route('admin.analytics', ['preset' => 'custom', 'from' => $from, 'to' => $to]))
            ->assertOk()
            ->assertSee('₹ 300.00')
            ->assertDontSee('₹ 400.00');
    }

    public function test_same_day_range_works(): void
    {
        $user = $this->makeUser();
        $this->createOrder($user, 250.00, 'cod', 'pending', 'pending', Carbon::now());

        $this->actingAs($this->manager(), 'admin')
            ->get(route('admin.analytics', ['preset' => 'today']))
            ->assertOk()
            ->assertSee('₹ 250.00');
    }

    public function test_rejects_custom_range_with_start_after_end(): void
    {
        $this->actingAs($this->manager(), 'admin')
            ->from(route('admin.analytics'))
            ->get(route('admin.analytics', ['preset' => 'custom', 'from' => '2026-02-10', 'to' => '2026-02-01']))
            ->assertRedirect(route('admin.analytics'))
            ->assertSessionHasErrors('range');
    }

    public function test_rejects_custom_range_without_both_dates(): void
    {
        $this->actingAs($this->manager(), 'admin')
            ->from(route('admin.analytics'))
            ->get(route('admin.analytics', ['preset' => 'custom', 'from' => '2026-02-01']))
            ->assertRedirect(route('admin.analytics'))
            ->assertSessionHasErrors('range');
    }

    public function test_rejects_range_larger_than_max_allowed(): void
    {
        $this->actingAs($this->manager(), 'admin')
            ->from(route('admin.analytics'))
            ->get(route('admin.analytics', ['preset' => 'custom', 'from' => '2020-01-01', 'to' => '2026-01-01']))
            ->assertRedirect(route('admin.analytics'))
            ->assertSessionHasErrors('range');
    }

    /*
    |--------------------------------------------------------------------------
    | Revenue / AOV / orders
    |--------------------------------------------------------------------------
    */

    public function test_revenue_and_aov_are_computed_correctly(): void
    {
        [$from, $to] = $this->range();
        $user = $this->makeUser();
        $this->createOrder($user, 1100.00, 'cod', 'pending', 'pending', Carbon::parse($to.' 10:00:00'));
        $this->createOrder($user, 2200.00, 'razorpay', 'paid', 'pending', Carbon::parse($to.' 11:00:00'));

        $report = $this->service->report($from, $to);

        $this->assertSame(3300.0, $report['revenue']['revenue']);
        $this->assertSame(2, $report['revenue']['orders']);
        $this->assertSame(1650.0, $report['revenue']['avg_order_value']);
        $this->assertSame(2, $report['executive']['orders']);
    }

    public function test_zero_data_renders_without_nan(): void
    {
        [$from, $to] = $this->range();
        $report = $this->service->report($from, $to);

        $this->assertSame(0.0, $report['revenue']['revenue']);
        $this->assertSame(0, $report['revenue']['orders']);
        $this->assertSame(0.0, $report['revenue']['avg_order_value']);

        $this->actingAs($this->manager(), 'admin')
            ->get(route('admin.analytics'))
            ->assertOk()
            ->assertDontSee('NaN');
    }

    public function test_excludes_cancelled_failed_refunded_and_unpaid_online_orders_from_revenue(): void
    {
        [$from, $to] = $this->range();
        $user = $this->makeUser();
        $at = fn (int $hoursAgo) => Carbon::parse($to.' 12:00:00')->subHours($hoursAgo);

        $this->createOrder($user, 500.00, 'cod', 'pending', 'pending', $at(1));                 // qualifying
        $this->createOrder($user, 600.00, 'cod', 'pending', 'cancelled', $at(2));               // cancelled
        $this->createOrder($user, 700.00, 'cod', 'pending', 'failed', $at(3));                  // failed
        $this->createOrder($user, 800.00, 'cod', 'refunded', 'delivered', $at(4));              // refunded
        $this->createOrder($user, 900.00, 'cod', 'partially_refunded', 'delivered', $at(5));    // partial
        $this->createOrder($user, 1000.00, 'razorpay', 'pending', 'pending', $at(6));           // online unpaid

        $report = $this->service->report($from, $to);

        $this->assertSame(500.0, $report['revenue']['revenue']);
        $this->assertSame(6, $report['funnel']['all_orders']);
        $this->assertSame(1, $report['funnel']['qualifying_orders']);
        $this->assertSame(1, $report['revenue']['cancelled_orders']);
        $this->assertSame(1, $report['revenue']['failed_orders']);
        $this->assertSame(1, $report['revenue']['refunded_orders']);
        $this->assertSame(1, $report['revenue']['partially_refunded_orders']);
        $this->assertSame(1, $report['revenue']['paid_orders'] === 0 ? 1 : 0); // no paid online count distortion
    }

    public function test_cod_and_online_paid_both_count_as_qualifying(): void
    {
        [$from, $to] = $this->range();
        $user = $this->makeUser();
        $this->createOrder($user, 100.00, 'cod', 'pending', 'pending', Carbon::parse($to.' 10:00:00'));
        $this->createOrder($user, 100.00, 'razorpay', 'paid', 'pending', Carbon::parse($to.' 11:00:00'));

        $report = $this->service->report($from, $to);

        $this->assertSame(2, $report['revenue']['orders']);
        $this->assertSame(1, $report['revenue']['cod_orders']);
        $this->assertSame(1, $report['revenue']['paid_orders']);
    }

    /*
    |--------------------------------------------------------------------------
    | Product analytics
    |--------------------------------------------------------------------------
    */

    public function test_product_revenue_uses_historic_item_snapshot_not_current_price(): void
    {
        [$from, $to] = $this->range();
        $user = $this->makeUser();
        $product = $this->makeProduct('SNAP-001', 100.0);
        $order = $this->createOrder($user, 300.00, 'cod', 'pending', 'pending', Carbon::parse($to.' 10:00:00'));
        $this->addItem($order, $product, 3, 100.0);

        $product->update(['selling_price' => 999.99]);

        $report = $this->service->report($from, $to);
        $productReport = $this->service->productReport($from, $to);

        $top = collect($report['products']['top_by_revenue'])->first();
        $this->assertSame('SNAP-001', $top->sku);
        $this->assertSame('300', (string) $top->revenue);
        $this->assertSame(3, (int) $top->units);

        $this->assertCount(1, $productReport['items']);
        $this->assertSame(300.0, (float) $productReport['items'][0]['revenue']);
    }

    public function test_products_aggregate_units_revenue_and_orders_by_sku(): void
    {
        [$from, $to] = $this->range();
        $user = $this->makeUser();
        $product = $this->makeProduct('AGG-001', 50.0);

        $order1 = $this->createOrder($user, 100.00, 'cod', 'pending', 'pending', Carbon::parse($to.' 10:00:00'));
        $this->addItem($order1, $product, 2, 50.0);

        $order2 = $this->createOrder($user, 50.00, 'razorpay', 'paid', 'pending', Carbon::parse($to.' 11:00:00'));
        $this->addItem($order2, $product, 1, 50.0);

        $productReport = $this->service->productReport($from, $to);

        $this->assertCount(1, $productReport['items']);
        $this->assertSame(3, (int) $productReport['items'][0]['units']);
        $this->assertSame(2, (int) $productReport['items'][0]['orders']);
        $this->assertSame(150.0, (float) $productReport['items'][0]['revenue']);
    }

    public function test_product_report_paginates(): void
    {
        [$from, $to] = $this->range();
        $user = $this->makeUser();

        for ($i = 0; $i < 17; $i++) {
            $product = $this->makeProduct('PAGE-'.str_pad((string) $i, 3, '0', STR_PAD_LEFT), 10.0);
            $order = $this->createOrder($user, 10.00, 'cod', 'pending', 'pending', Carbon::parse($to.' 10:00:00'));
            $this->addItem($order, $product, 1, 10.0);
        }

        $page1 = $this->service->productReport($from, $to, 1);
        $page2 = $this->service->productReport($from, $to, 2);

        $this->assertSame(17, $page1['total']);
        $this->assertSame(2, $page1['last_page']);
        $this->assertCount(15, $page1['items']);
        $this->assertCount(2, $page2['items']);
    }

    /*
    |--------------------------------------------------------------------------
    | Dadi analytics
    |--------------------------------------------------------------------------
    */

    public function test_dadi_metrics_and_attribution_avoid_double_counting(): void
    {
        [$from, $to] = $this->range();
        $user = $this->makeUser();
        $productA = $this->makeProduct('DADI-A', 100.0);
        $productB = $this->makeProduct('DADI-B', 100.0);

        $order = $this->createOrder($user, 5000.00, 'cod', 'pending', 'pending', Carbon::parse($to.' 10:00:00'), 0.0, 'VAN-DADI-000001');
        $this->addItem($order, $productA, 1, 100.0);
        $this->addItem($order, $productB, 1, 100.0);

        $at = Carbon::parse($to.' 14:00:00');
        $this->dadiEvent($productA, 'impression', null, $at);
        $this->dadiEvent($productA, 'impression', null, $at);
        $this->dadiEvent($productB, 'impression', null, $at);
        $this->dadiEvent($productB, 'impression', null, $at);
        $this->dadiEvent($productA, 'click', null, $at);
        $this->dadiEvent($productA, 'click', null, $at);
        $this->dadiEvent($productB, 'add_to_cart', null, $at);
        $this->dadiEvent($productA, 'buy_now', null, $at);

        // Two products purchased inside the SAME order => one attributed order.
        $this->dadiEvent($productA, 'purchase', $order->order_number, $at);
        $this->dadiEvent($productB, 'purchase', $order->order_number, $at);

        $conv = DadiConversation::create([
            'user_id' => $user->id,
            'status' => 'active',
            'locale' => 'en',
            'state' => [],
            'created_at' => $at,
            'updated_at' => $at,
        ]);
        $this->forceTimestamps('dadi_conversations', $conv->id, $at);

        $guest = DadiConversation::create([
            'status' => 'active',
            'session_id' => 'sess-guest-1',
            'locale' => 'en',
            'state' => [],
            'created_at' => $at,
            'updated_at' => $at,
        ]);
        $this->forceTimestamps('dadi_conversations', $guest->id, $at);

        $report = $this->service->report($from, $to);

        $this->assertSame(4, $report['dadi']['impressions']);
        $this->assertSame(2, $report['dadi']['clicks']);
        $this->assertSame(1, $report['dadi']['add_to_cart']);
        $this->assertSame(1, $report['dadi']['buy_now']);
        $this->assertSame(1, $report['dadi']['purchases'], 'purchases must count distinct orders');
        $this->assertSame(5000.0, $report['dadi']['attributed_revenue'], 'revenue must be attributed once per order');
        $this->assertSame(50.0, $report['dadi']['ctr']);
        $this->assertSame(50.0, $report['dadi']['cvr']);
        $this->assertSame(4, $report['dadi']['top_impressions']->sum('count'), 'impression sum matches stored rows');
        $this->assertSame(2, $report['dadi']['conversations']['total']);
        $this->assertSame(1, $report['dadi']['conversations']['guest']);
        $this->assertSame(1, $report['dadi']['conversations']['authenticated']);
        $this->assertSame(['active' => 2], $report['dadi']['conversations']['by_status']);
    }

    public function test_dadi_zero_denominators_are_safe(): void
    {
        [$from, $to] = $this->range();

        $report = $this->service->report($from, $to);

        $this->assertSame(0, $report['dadi']['impressions']);
        $this->assertSame(0, $report['dadi']['purchases']);
        $this->assertNull($report['dadi']['ctr']);
        $this->assertNull($report['dadi']['cvr']);

        $this->actingAs($this->manager(), 'admin')
            ->get(route('admin.analytics'))
            ->assertOk()
            ->assertDontSee('NaN');
    }

    /*
    |--------------------------------------------------------------------------
    | Tracking diagnostics
    |--------------------------------------------------------------------------
    */

    public function test_pixel_is_masked_and_capi_status_is_dynamic(): void
    {
        config()->set('analytics.gtm_container_id', '');
        Setting::where('key', 'meta_pixel_id')->update(['value' => '2252733575515299']);

        $response = $this->actingAs($this->manager(), 'admin')
            ->get(route('admin.analytics'))
            ->assertOk();

        $response->assertSee('xxxx5299');
        $response->assertDontSee('2252733575515299');
        $response->assertSee('NOT CONFIGURED');
        $response->assertSee('Meta Conversions API is not configured');
        $response->assertDontSee('Check Settings');
    }

    public function test_missing_pixel_raises_critical_alert(): void
    {
        config()->set('analytics.gtm_container_id', '');

        $this->actingAs($this->manager(), 'admin')
            ->get(route('admin.analytics'))
            ->assertOk()
            ->assertSee('Meta Pixel is not configured')
            ->assertSee('CRITICAL');
    }

    public function test_gtm_configuration_surfaces_in_diagnostics(): void
    {
        config()->set('analytics.gtm_container_id', 'GTM-ABC123');

        $this->actingAs($this->manager(), 'admin')
            ->get(route('admin.analytics'))
            ->assertOk()
            ->assertSee('GTM-ABC123');
    }

    public function test_ledger_states_and_failures_are_diagnosed(): void
    {
        [$from, $to] = $this->range();
        $at = Carbon::parse($to.' 09:00:00');

        $nullState = AnalyticsConversion::create([
            'event_type' => 'purchase',
            'order_number' => 'VAN-LEDGER-1',
            'channel' => 'meta',
            'payload' => [],
            'meta_state' => null,
            'meta_attempts' => 0,
            'created_at' => $at,
            'updated_at' => $at,
        ]);
        $this->forceTimestamps('analytics_conversions', $nullState->id, $at);

        $failedState = AnalyticsConversion::create([
            'event_type' => 'purchase',
            'order_number' => 'VAN-LEDGER-2',
            'channel' => 'meta',
            'payload' => [],
            'meta_state' => 'failed',
            'meta_attempts' => 3,
            'created_at' => $at,
            'updated_at' => $at,
        ]);
        $this->forceTimestamps('analytics_conversions', $failedState->id, $at);

        $report = $this->service->report($from, $to);

        $this->assertSame(2, $report['tracking']['ledger_total']);
        $this->assertSame(['null' => 1, 'failed' => 1], $report['tracking']['ledger_by_state']);
        $this->assertSame(1, $report['tracking']['ledger_failures_7d']);

        $this->actingAs($this->manager(), 'admin')
            ->get(route('admin.analytics'))
            ->assertOk()
            ->assertSee('failed (7 day): 1');
    }

    public function test_missing_skus_are_flagged(): void
    {
        config()->set('analytics.gtm_container_id', '');

        $this->makeProduct('UNIQ-001');
        Product::factory()->create(['sku' => null, 'stock' => 5, 'low_stock_threshold' => 2, 'status' => 'active']);

        $this->actingAs($this->manager(), 'admin')
            ->get(route('admin.analytics'))
            ->assertOk()
            ->assertSee('Products missing SKU');
    }

    public function test_tracking_coverage_counts_ledger_tracked_orders(): void
    {
        [$from, $to] = $this->range();
        $user = $this->makeUser();

        $tracked = $this->createOrder($user, 100.00, 'cod', 'pending', 'pending', Carbon::parse($to.' 10:00:00'), 0.0, 'VAN-TRACKED-1');
        $this->createOrder($user, 200.00, 'cod', 'pending', 'pending', Carbon::parse($to.' 11:00:00'), 0.0, 'VAN-UNTRACKED-1');

        $ledger = AnalyticsConversion::create([
            'event_type' => 'purchase',
            'order_number' => $tracked->order_number,
            'channel' => 'meta',
            'payload' => [],
            'meta_state' => null,
            'meta_attempts' => 0,
            'created_at' => Carbon::parse($to.' 10:30:00'),
            'updated_at' => Carbon::parse($to.' 10:30:00'),
        ]);
        $this->forceTimestamps('analytics_conversions', $ledger->id, Carbon::parse($to.' 10:30:00'));

        $report = $this->service->report($from, $to);

        $this->assertSame(1, $report['tracking']['tracked_orders']);
        $this->assertSame(1, $report['tracking']['untracked_orders']);
        $this->assertSame(50.0, $report['tracking']['coverage_percent']);
    }

    public function test_conversion_rate_kpi_renders_em_dash_instead_of_literal_entity(): void
    {
        $response = $this->actingAs($this->manager(), 'admin')
            ->get(route('admin.analytics'))
            ->assertOk();

        $html = $response->getContent();
        $start = strpos($html, 'Conversion Rate');
        $this->assertNotFalse($start, 'Conversion Rate KPI should be present');
        $segment = substr($html, $start, 160);

        $this->assertStringContainsString('—', $segment, 'KPI should render a real em dash when there is no conversion denominator');
        $this->assertStringNotContainsString('&amp;mdash;', $segment, 'KPI must not render the escaped literal string "&mdash;"');
    }

    /*
    |--------------------------------------------------------------------------
    | Security
    |--------------------------------------------------------------------------
    */

    public function test_no_customer_pii_is_rendered(): void
    {
        [$from, $to] = $this->range();
        $user = User::factory()->create([
            'name' => 'Privacy Probe',
            'email' => 'privacy-probe-'.Str::random(8).'@example.com',
            'phone' => '9812345678',
        ]);

        $this->createOrder($user, 500.00, 'cod', 'pending', 'pending', Carbon::parse($to.' 10:00:00'));

        $response = $this->actingAs($this->manager(), 'admin')
            ->get(route('admin.analytics'))
            ->assertOk();

        $response->assertDontSee($user->email);
        $response->assertDontSee('9812345678');
        $response->assertDontSee('Privacy Probe');
    }

    public function test_no_secret_values_are_rendered(): void
    {
        $this->actingAs($this->manager(), 'admin')
            ->get(route('admin.analytics'))
            ->assertOk()
            ->assertDontSee('meta_capi_access_token')
            ->assertDontSee('razorpay_key_secret');
    }

    /*
    |--------------------------------------------------------------------------
    | Caching
    |--------------------------------------------------------------------------
    */

    public function test_report_is_cached_and_key_contains_date_range(): void
    {
        Cache::flush();

        $report = $this->service->report('2026-01-01', '2026-01-31');

        $key = $this->service->cacheKey('2026-01-01', '2026-01-31');
        $this->assertTrue(Cache::has($key));
        $this->assertArrayHasKey('executive', $report);

        $ranges = ['2026-01-01', '2026-01-31', '2026-02-01', '2026-02-28', '2026-03-01', '2026-03-31', '2026-04-01', '2026-04-30', '2026-05-01', '2026-05-31', '2026-06-01', '2026-06-30'];
        $rendered = $this->service->report($ranges[0], $ranges[1]);

        $this->assertSame($report, $rendered);
        $this->assertFalse(Cache::has($this->service->cacheKey('2026-02-01', '2026-02-28')));
    }

    public function test_cached_result_can_be_evicted_by_range_change(): void
    {
        $this->service->report('2026-01-01', '2026-01-31');
        $this->assertTrue(Cache::has($this->service->cacheKey('2026-01-01', '2026-01-31')));
        $this->assertFalse(Cache::has($this->service->cacheKey('2026-01-01', '2026-02-28')));
    }

    /*
    |--------------------------------------------------------------------------
    | First-party analytics_events
    |--------------------------------------------------------------------------
    */

    public function test_events_section_counts_unique_sessions_and_event_types(): void
    {
        [$from, $to] = $this->range();
        $this->recordEvent(AnalyticsEvent::PAGE_VIEW, ['session_id' => 'sess-a', 'occurred_at' => Carbon::parse($to.' 10:00:00')]);
        $this->recordEvent(AnalyticsEvent::PAGE_VIEW, ['session_id' => 'sess-a', 'occurred_at' => Carbon::parse($to.' 10:05:00')]);
        $this->recordEvent(AnalyticsEvent::PAGE_VIEW, ['session_id' => 'sess-b', 'occurred_at' => Carbon::parse($to.' 11:00:00')]);
        $this->recordEvent(AnalyticsEvent::VIEW_ITEM, ['session_id' => 'sess-a', 'occurred_at' => Carbon::parse($to.' 10:10:00')]);
        $this->recordEvent(AnalyticsEvent::VIEW_ITEM, ['session_id' => 'sess-b', 'occurred_at' => Carbon::parse($to.' 11:05:00')]);
        $this->recordEvent(AnalyticsEvent::ADD_TO_CART, ['session_id' => 'sess-b', 'occurred_at' => Carbon::parse($to.' 11:10:00')]);
        $this->recordEvent(AnalyticsEvent::BEGIN_CHECKOUT, ['session_id' => 'sess-b', 'occurred_at' => Carbon::parse($to.' 11:15:00')]);

        $report = $this->service->report($from, $to);

        $this->assertSame(2, $report['events']['visitors']);
        $this->assertSame(3, $report['events']['page_views']);
        $this->assertSame(2, $report['events']['product_views']);
        $this->assertSame(1, $report['events']['add_to_carts']);
        $this->assertSame(1, $report['events']['begin_checkouts']);
        $this->assertSame(0, $report['events']['add_payment_infos']);
    }

    public function test_traffic_section_aggregates_by_source_medium_landing_and_device(): void
    {
        [$from, $to] = $this->range();
        $this->recordEvent(AnalyticsEvent::PAGE_VIEW, [
            'session_id' => 's1',
            'source' => 'google', 'medium' => 'cpc', 'campaign' => 'camp-a',
            'landing_path' => '/', 'device_type' => 'mobile',
            'occurred_at' => Carbon::parse($to.' 10:00:00'),
        ]);
        $this->recordEvent(AnalyticsEvent::PAGE_VIEW, [
            'session_id' => 's1',
            'source' => 'google', 'medium' => 'cpc', 'campaign' => 'camp-a',
            'landing_path' => '/products/snapback', 'device_type' => 'mobile',
            'occurred_at' => Carbon::parse($to.' 10:05:00'),
        ]);
        $this->recordEvent(AnalyticsEvent::PAGE_VIEW, [
            'session_id' => 's2',
            'source' => 'direct', 'medium' => 'direct', 'campaign' => null,
            'landing_path' => '/', 'device_type' => 'desktop',
            'occurred_at' => Carbon::parse($to.' 11:00:00'),
        ]);

        $report = $this->service->report($from, $to);

        $this->assertSame(2, $report['events']['visitors']);
        $this->assertSame(['google' => 2, 'direct' => 1], collect($report['traffic']['by_source'])->mapWithKeys(fn ($r) => [$r['label'] => $r['views']])->all());
        $this->assertSame(['cpc' => 2, 'direct' => 1], collect($report['traffic']['by_medium'])->mapWithKeys(fn ($r) => [$r['label'] => $r['views']])->all());
        $this->assertSame(['camp-a' => 2], collect($report['traffic']['by_campaign'])->mapWithKeys(fn ($r) => [$r['label'] => $r['views']])->all());
        $this->assertSame(['/' => 2, '/products/snapback' => 1], collect($report['traffic']['by_landing'])->mapWithKeys(fn ($r) => [$r['label'] => $r['views']])->all());
        $this->assertSame(['mobile' => 2, 'desktop' => 1], collect($report['traffic']['by_device'])->mapWithKeys(fn ($r) => [$r['label'] => $r['views']])->all());
    }

    public function test_payment_started_counts_distinct_qualifying_orders_with_payments_activity(): void
    {
        [$from, $to] = $this->range();
        $user = $this->makeUser();
        $qualifying = $this->createOrder($user, 500.00, 'cod', 'pending', 'pending', Carbon::parse($to.' 09:00:00'));
        $cancelled = $this->createOrder($user, 600.00, 'cod', 'pending', 'cancelled', Carbon::parse($to.' 09:30:00'));
        $dual = $this->createOrder($user, 700.00, 'cod', 'pending', 'pending', Carbon::parse($to.' 10:00:00'));

        foreach ([$qualifying, $cancelled, $dual] as $order) {
            DB::table('payments')->insert([
                'order_id' => $order->id,
                'method' => 'cod',
                'amount' => $order->grand_total,
                'status' => 'pending',
                'created_at' => Carbon::parse($to.' 10:30:00'),
                'updated_at' => Carbon::parse($to.' 10:30:00'),
            ]);
        }
        // a second payment on the same qualifying order must not double-count
        DB::table('payments')->insert([
            'order_id' => $qualifying->id,
            'method' => 'cod',
            'amount' => 100.00,
            'status' => 'paid',
            'created_at' => Carbon::parse($to.' 11:00:00'),
            'updated_at' => Carbon::parse($to.' 11:00:00'),
        ]);

        $report = $this->service->report($from, $to);

        $this->assertSame(2, $report['payment_started']['count']);
    }

    public function test_compare_provides_previous_equal_length_period(): void
    {
        [$from, $to] = $this->range();
        $user = $this->makeUser();

        $days = Carbon::parse($from)->diffInDays(Carbon::parse($to)) + 1;
        $endPrev = Carbon::parse($from)->subDay();
        $startPrev = $endPrev->copy()->subDays($days - 1);

        $this->createOrder($user, 100.00, 'cod', 'pending', 'pending', Carbon::parse($to.' 10:00:00'));
        $this->createOrder($user, 250.00, 'cod', 'pending', 'pending', $endPrev->copy()->setTime(10, 0, 0));
        $this->recordEvent(AnalyticsEvent::PAGE_VIEW, ['session_id' => 'prev-sess', 'occurred_at' => $endPrev->copy()->setTime(11, 0, 0)]);

        $report = $this->service->report($from, $to);

        $this->assertSame($startPrev->toDateString(), $report['compare']['from']);
        $this->assertSame($endPrev->toDateString(), $report['compare']['to']);
        $this->assertSame($days, $report['compare']['days']);
        $this->assertSame(100.0, $report['executive']['revenue']);
        $this->assertSame(250.0, $report['compare']['executive']['revenue']);
        $this->assertSame(1, $report['compare']['events']['visitors']);
    }

    public function test_product_event_report_merges_order_and_event_metrics_by_sku(): void
    {
        [$from, $to] = $this->range();
        $user = $this->makeUser();
        $product = $this->makeProduct('EVENT-001', 100.0);
        $order = $this->createOrder($user, 200.00, 'cod', 'pending', 'pending', Carbon::parse($to.' 10:00:00'));
        $this->addItem($order, $product, 2, 100.0);

        $this->recordEvent(AnalyticsEvent::VIEW_ITEM, ['session_id' => 's1', 'product_id' => $product->id, 'sku' => 'EVENT-001', 'occurred_at' => Carbon::parse($to.' 09:00:00')]);
        $this->recordEvent(AnalyticsEvent::VIEW_ITEM, ['session_id' => 's2', 'product_id' => $product->id, 'sku' => 'EVENT-001', 'occurred_at' => Carbon::parse($to.' 09:05:00')]);
        $this->recordEvent(AnalyticsEvent::ADD_TO_CART, ['session_id' => 's1', 'product_id' => $product->id, 'sku' => 'EVENT-001', 'occurred_at' => Carbon::parse($to.' 09:10:00')]);
        $this->recordEvent(AnalyticsEvent::BEGIN_CHECKOUT, ['session_id' => 's1', 'product_id' => $product->id, 'sku' => 'EVENT-001', 'occurred_at' => Carbon::parse($to.' 09:15:00')]);

        $report = $this->service->productEventReport($from, $to);

        $this->assertCount(1, $report['items']);
        $row = $report['items'][0];
        $this->assertSame('EVENT-001', $row['sku']);
        $this->assertSame(2, (int) $row['views']);
        $this->assertSame(1, (int) $row['add_to_carts']);
        $this->assertSame(1, (int) $row['checkouts']);
        $this->assertSame(1, (int) $row['purchases']);
        $this->assertSame(2, (int) $row['units']);
        $this->assertSame(200.0, (float) $row['revenue']);
        $this->assertSame(50.0, (float) $row['conversion_rate']);
    }

    public function test_product_event_report_filters_by_name_and_category(): void
    {
        [$from, $to] = $this->range();
        $category = Category::factory()->create();
        $matched = Product::factory()->create(['sku' => 'FILT-AAA-001', 'name' => 'Alpha Snapback', 'selling_price' => 100.0, 'mrp' => 100.0, 'stock' => 10, 'low_stock_threshold' => 2, 'status' => 'active']);
        $other = Product::factory()->create(['sku' => 'FILT-BBB-002', 'name' => 'Beta Snapback', 'selling_price' => 100.0, 'mrp' => 100.0, 'stock' => 10, 'low_stock_threshold' => 2, 'status' => 'active']);

        DB::table('product_category')->insert([
            'product_id' => $matched->id,
            'category_id' => $category->id,
        ]);

        foreach (['FILT-AAA-001', 'FILT-BBB-002'] as $i => $sku) {
            $this->recordEvent(AnalyticsEvent::VIEW_ITEM, ['session_id' => 's'.$i, 'sku' => $sku, 'occurred_at' => Carbon::parse($to.' 09:00:00')]);
        }

        $byName = $this->service->productEventReport($from, $to, 'Alpha', null);
        $this->assertCount(1, $byName['items']);
        $this->assertSame('FILT-AAA-001', $byName['items'][0]['sku']);

        $byCategory = $this->service->productEventReport($from, $to, null, $category->id);
        $this->assertCount(1, $byCategory['items']);
        $this->assertSame('FILT-AAA-001', $byCategory['items'][0]['sku']);

        $all = $this->service->productEventReport($from, $to);
        $this->assertCount(2, $all['items']);
    }

    public function test_product_event_report_lists_event_only_skus_as_unmatched(): void
    {
        [$from, $to] = $this->range();

        $this->recordEvent(AnalyticsEvent::VIEW_ITEM, ['session_id' => 's1', 'sku' => 'GHOST-999', 'occurred_at' => Carbon::parse($to.' 09:00:00')]);

        $report = $this->service->productEventReport($from, $to);

        $this->assertCount(1, $report['items']);
        $this->assertSame('GHOST-999', $report['items'][0]['sku']);
        $this->assertSame('(unmatched)', $report['items'][0]['product_name']);
        $this->assertSame(1, (int) $report['items'][0]['views']);
    }

    public function test_capi_status_is_derived_from_ledger_evidence_not_config_alone(): void
    {
        [$from, $to] = $this->range();
        Setting::where('key', 'meta_pixel_id')->update(['value' => '2252733575515299']);
        Setting::where('key', 'meta_capi_access_token')->update(['value' => Crypt::encryptString('test-token')]);
        config()->set('meta.enabled', true);

        // configured with zero ledger rows -> unknown (config alone proves nothing)
        $report = $this->service->report($from, $to);
        $this->assertSame('unknown', $report['tracking']['capi']['status']);

        // a ledger row with no recent delivery -> configured_not_receiving
        $this->ledgerRow('sent', Carbon::now()->subDays(20));
        Cache::flush();
        $report = $this->service->report($from, $to);
        $this->assertSame('configured_not_receiving', $report['tracking']['capi']['status']);

        // recent delivered row -> active
        $this->ledgerRow('sent', Carbon::now()->subHours(2));
        Cache::flush();
        $report = $this->service->report($from, $to);
        $this->assertSame('active', $report['tracking']['capi']['status']);
    }

    public function test_capi_status_is_failed_when_recent_failures_and_no_delivery(): void
    {
        [$from, $to] = $this->range();
        Setting::where('key', 'meta_pixel_id')->update(['value' => '2252733575515299']);
        Setting::where('key', 'meta_capi_access_token')->update(['value' => Crypt::encryptString('test-token')]);
        config()->set('meta.enabled', true);

        $this->ledgerRow('failed', Carbon::now()->subHours(3));

        $report = $this->service->report($from, $to);
        $this->assertSame('failed', $report['tracking']['capi']['status']);
    }
}
