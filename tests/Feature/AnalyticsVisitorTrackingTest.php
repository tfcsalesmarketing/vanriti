<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\AnalyticsEvent;
use App\Models\Product;
use App\Models\Role;
use App\Services\Analytics\AnalyticsCommandCenterService;
use App\Services\Analytics\AnalyticsEventRecorder;
use App\Services\Analytics\EcommerceDataService;
use App\Services\Analytics\VisitorIdentity;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Cookie as HttpCookie;
use Tests\TestCase;

/**
 * Phase 1.1 — persistent anonymous visitor tracking.
 *
 * Covers the cookie contract, propagation onto every recorded event, the
 * unique/new/returning/session definitions, historical classification,
 * previous-period comparison, daily series, diagnostics, and the safety and
 * privacy guarantees the commerce side depends on.
 */
class AnalyticsVisitorTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected AnalyticsCommandCenterService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SettingsSeeder::class);

        $this->service = app(AnalyticsCommandCenterService::class);
    }

    /**
     * Insert a page_view directly, bypassing the recorder, so aggregation can
     * be tested independently of cookie behaviour.
     */
    protected function pageView(
        ?string $visitor,
        string $session,
        string $occurredAt,
        array $attributes = [],
    ): AnalyticsEvent {
        return AnalyticsEvent::create(array_merge([
            'event_type' => AnalyticsEvent::PAGE_VIEW,
            'session_id' => hash('sha256', $session),
            'visitor_id' => $visitor,
            'occurred_at' => $occurredAt,
        ], $attributes));
    }

    protected function uuid(int $seed): string
    {
        return sprintf('00000000-0000-4000-8000-%012d', $seed);
    }

    protected function makeProduct(string $sku = 'VIS-001'): Product
    {
        return Product::factory()->create([
            'sku' => $sku,
            'selling_price' => 100.0,
            'mrp' => 100.0,
            'stock' => 25,
            'status' => 'active',
        ]);
    }

    /**
     * Resolve the queued visitor cookie, or null when none was queued.
     */
    protected function queuedVisitorCookie(): ?HttpCookie
    {
        return collect(Cookie::getQueuedCookies())
            ->first(fn ($c) => $c->getName() === VisitorIdentity::COOKIE_NAME);
    }

    // ---------------------------------------------------------------------
    // Cookie contract
    // ---------------------------------------------------------------------

    public function test_a_visitor_cookie_is_minted_when_absent(): void
    {
        $id = (new VisitorIdentity)->resolve(Request::create('/'));

        $this->assertNotNull($id);
        $this->assertTrue(Str::isUuid($id), 'visitor id must be a UUID');

        $cookie = $this->queuedVisitorCookie();

        $this->assertNotNull($cookie, 'a Set-Cookie must be queued');
        $this->assertSame($id, $cookie->getValue());
    }

    public function test_an_existing_valid_uuid_is_reused_and_not_reissued(): void
    {
        $existing = $this->uuid(7);
        $request = Request::create('/');
        $request->cookies->set(VisitorIdentity::COOKIE_NAME, $existing);

        $identity = new VisitorIdentity;

        $this->assertSame($existing, $identity->resolve($request));
        $this->assertSame($existing, $identity->resolve($request), 'identity is memoised per request');
        $this->assertNull($this->queuedVisitorCookie(), 'an existing valid identity must not be reissued');
    }

    public function test_a_malformed_cookie_is_replaced_not_trusted(): void
    {
        $request = Request::create('/');
        $request->cookies->set(VisitorIdentity::COOKIE_NAME, 'not-a-uuid');

        $id = (new VisitorIdentity)->resolve($request);

        $this->assertNotNull($id);
        $this->assertNotSame('not-a-uuid', $id);
        $this->assertTrue(Str::isUuid($id));
    }

    public function test_the_cookie_is_host_only_httponly_lax_and_https_aware(): void
    {
        (new VisitorIdentity)->resolve(Request::create('https://vanriti.test/'));

        $cookie = $this->queuedVisitorCookie();

        $this->assertNotNull($cookie);
        $this->assertNull($cookie->getDomain(), 'must be host-only: no Domain attribute');
        $this->assertTrue($cookie->isHttpOnly(), 'must be HttpOnly');
        $this->assertSame('lax', $cookie->getSameSite());
        $this->assertTrue($cookie->isSecure(), 'must be Secure over HTTPS');
        $this->assertSame('/', $cookie->getPath());
    }

    public function test_the_cookie_is_not_secure_over_plain_http(): void
    {
        (new VisitorIdentity)->resolve(Request::create('http://vanriti.test/'));

        $cookie = $this->queuedVisitorCookie();

        $this->assertNotNull($cookie);
        $this->assertFalse($cookie->isSecure());
    }

    public function test_the_cookie_name_is_a_scoped_first_party_identifier(): void
    {
        $this->assertSame('vanriti_visitor_id', VisitorIdentity::COOKIE_NAME);
    }

    // ---------------------------------------------------------------------
    // Event propagation
    // ---------------------------------------------------------------------

    public function test_every_recorded_event_carries_the_visitor_id(): void
    {
        $visitor = $this->uuid(11);
        $request = Request::create('/products/tee');
        $request->cookies->set(VisitorIdentity::COOKIE_NAME, $visitor);
        $this->app->instance('request', $request);

        $product = $this->makeProduct('VIS-ITM-1');

        $recorder = app(AnalyticsEventRecorder::class);
        $recorder->pageView($request);
        $recorder->viewItem($product);
        $recorder->addToCart($product, null, 2);

        $events = AnalyticsEvent::whereIn('event_type', [
            AnalyticsEvent::PAGE_VIEW,
            AnalyticsEvent::VIEW_ITEM,
            AnalyticsEvent::ADD_TO_CART,
        ])->get();

        $this->assertCount(3, $events);
        $this->assertTrue(
            $events->every(fn ($e) => $e->visitor_id === $visitor),
            'page_view, view_item and add_to_cart must all carry visitor_id'
        );
    }

    public function test_page_view_carries_the_visitor_id(): void
    {
        $visitor = $this->uuid(12);
        $request = Request::create('/products/tee');
        $request->cookies->set(VisitorIdentity::COOKIE_NAME, $visitor);

        app(AnalyticsEventRecorder::class)->pageView($request);

        $event = AnalyticsEvent::where('event_type', AnalyticsEvent::PAGE_VIEW)->latest('id')->first();

        $this->assertNotNull($event);
        $this->assertSame($visitor, $event->visitor_id);
    }

    public function test_identity_is_not_derived_from_the_session_id(): void
    {
        $visitor = $this->uuid(13);

        // First visit, session A, no cookie yet: an identity is minted.
        $first = Request::create('/products/tee');
        $this->app->instance('request', $first);
        app(AnalyticsEventRecorder::class)->viewItem($this->makeProduct('VIS-A'));
        $minted = AnalyticsEvent::latest('id')->first()->visitor_id;

        $this->assertNotNull($minted, 'an identity is minted on the first visit');

        // A brand new Laravel session carrying the same first-party cookie must
        // resolve to the same visitor, proving identity is not session-derived.
        $second = Request::create('/shop');
        $second->cookies->set(VisitorIdentity::COOKIE_NAME, $minted);
        session()->flush();
        session()->setId($this->uuid(900));

        app(AnalyticsEventRecorder::class)->pageView($second);

        $visitors = AnalyticsEvent::pluck('visitor_id')->unique()->all();

        $this->assertSame([$minted], $visitors, 'one browser identity spans many sessions');
        $this->assertNotSame(
            AnalyticsEventRecorder::sessionId(),
            $minted,
            'visitor_id and session_id are independent values'
        );
    }

    public function test_visitor_id_is_absent_rather_than_invented_when_no_cookie_is_available(): void
    {
        $this->pageView(null, 'session-a', now()->toDateTimeString());

        $event = AnalyticsEvent::where('event_type', AnalyticsEvent::PAGE_VIEW)->first();

        $this->assertNull($event->visitor_id, 'a missing identity must be null, never fabricated');
    }

    // ---------------------------------------------------------------------
    // Aggregation definitions
    // ---------------------------------------------------------------------

    public function test_visitors_are_counted_distinctly_across_sessions_and_pages(): void
    {
        $visitor = $this->uuid(21);
        $other = $this->uuid(22);
        $at = now()->subDays(2)->toDateTimeString();

        // One visitor across two sessions and three page views.
        $this->pageView($visitor, 's1', $at);
        $this->pageView($visitor, 's1', $at);
        $this->pageView($visitor, 's2', $at);
        // A second visitor in a single session.
        $this->pageView($other, 's2', $at);

        $report = $this->report();

        $this->assertSame(2, $report['visitors']['unique'], 'three page views from one visitor count once');
        $this->assertSame(2, $report['visitors']['sessions'], 'two distinct sessions');
    }

    public function test_only_page_view_contributes_to_visitor_counts(): void
    {
        $visitor = $this->uuid(23);
        $at = now()->subDays(2)->toDateTimeString();

        $this->pageView($visitor, 's1', $at);
        AnalyticsEvent::create([
            'event_type' => AnalyticsEvent::ADD_TO_CART,
            'session_id' => hash('sha256', 's1'),
            'visitor_id' => $visitor,
            'sku' => 'TEE-1',
            'occurred_at' => $at,
        ]);

        $report = $this->report();

        $this->assertSame(1, $report['visitors']['unique'], 'add_to_cart must not create a second visitor');
    }

    public function test_events_without_a_visitor_id_are_skipped_not_counted_as_unique(): void
    {
        $at = now()->subDays(2)->toDateTimeString();

        $this->pageView($this->uuid(24), 's1', $at);
        $this->pageView(null, 's2', $at);
        $this->pageView('', 's3', $at);

        $this->assertSame(1, $this->report()['visitors']['unique']);
    }

    public function test_new_and_returning_use_historical_first_ever_page_view(): void
    {
        $newcomer = $this->uuid(31);
        $veteran = $this->uuid(32);

        // The veteran was first seen 10 days ago, well before the window.
        $this->pageView($veteran, 'old', now()->subDays(10)->toDateTimeString());
        // Active again inside the window.
        $this->pageView($veteran, 'new', now()->subDays(2)->toDateTimeString());
        // The newcomer's first-ever visit is inside the window.
        $this->pageView($newcomer, 'fresh', now()->subDay()->toDateTimeString());

        $report = $this->report();

        $this->assertSame(2, $report['visitors']['unique']);
        $this->assertSame(1, $report['visitors']['new'], 'only the newcomer is new');
        $this->assertSame(1, $report['visitors']['returning'], 'the veteran is returning, not new');
        $this->assertSame(50.0, $report['visitors']['new_rate']);
        $this->assertSame(50.0, $report['visitors']['returning_rate']);
    }

    public function test_a_visitor_active_again_after_a_long_gap_is_returning_not_new(): void
    {
        $visitor = $this->uuid(33);

        $this->pageView($visitor, 's1', now()->subDays(30)->toDateTimeString());
        $this->pageView($visitor, 's2', now()->subDay()->toDateTimeString());

        $report = $this->report();

        $this->assertSame(1, $report['visitors']['unique']);
        $this->assertSame(0, $report['visitors']['new'], 'the first-ever view predates the window');
        $this->assertSame(1, $report['visitors']['returning']);
    }

    public function test_rates_are_null_when_there_are_no_visitors(): void
    {
        $report = $this->report();

        $this->assertNull($report['visitors']['new_rate']);
        $this->assertNull($report['visitors']['returning_rate']);
        $this->assertFalse($report['visitors']['tracked']);
    }

    public function test_the_daily_series_reconciles_new_plus_returning_with_visitors(): void
    {
        $a = $this->uuid(41);
        $b = $this->uuid(42);
        $c = $this->uuid(43);

        $this->pageView($a, 's1', now()->subDays(3)->startOfDay()->addHours(9)->toDateTimeString());
        $this->pageView($b, 's2', now()->subDays(3)->startOfDay()->addHours(11)->toDateTimeString());
        $this->pageView($c, 's3', now()->subDays(3)->startOfDay()->addHours(12)->toDateTimeString());
        // a and b return; c is a one-time visitor.
        $this->pageView($a, 's4', now()->subDay()->startOfDay()->addHours(10)->toDateTimeString());
        $this->pageView($b, 's5', now()->subDay()->startOfDay()->addHours(14)->toDateTimeString());
        // a and a again on the same day, to prove per-day de-duplication.
        $this->pageView($a, 's6', now()->subDay()->startOfDay()->addHours(18)->toDateTimeString());

        $series = collect($this->report()['visitors']['series'])->keyBy('date');

        $this->assertSame(3, $series[now()->subDays(3)->toDateString()]['visitors']);
        $this->assertSame(3, $series[now()->subDays(3)->toDateString()]['new'], 'all three are new on their first day');
        $this->assertSame(0, $series[now()->subDays(3)->toDateString()]['returning']);

        $this->assertSame(2, $series[now()->subDay()->toDateString()]['visitors'], 'a is counted once despite two page views');
        $this->assertSame(0, $series[now()->subDay()->toDateString()]['new']);
        $this->assertSame(2, $series[now()->subDay()->toDateString()]['returning']);

        foreach ($this->report()['visitors']['series'] as $day) {
            $this->assertSame(
                $day['visitors'],
                $day['new'] + $day['returning'],
                'new + returning must equal visitors for every day'
            );
        }
    }

    public function test_the_daily_series_is_gap_filled_across_the_period(): void
    {
        $this->pageView($this->uuid(51), 's1', now()->subDay()->toDateTimeString());

        $series = $this->report()['visitors']['series'];

        $this->assertCount(7, $series, 'a 7-day window yields 7 points regardless of activity');

        $today = collect($series)->firstWhere('date', now()->toDateString());

        $this->assertSame(0, $today['visitors'], 'today has no page views in this test');
        $this->assertSame(0, $today['new']);
        $this->assertSame(0, $today['returning']);

        $yesterday = collect($series)->firstWhere('date', now()->subDay()->toDateString());

        $this->assertSame(1, $yesterday['visitors']);
        $this->assertSame(1, $yesterday['new']);
    }

    public function test_visitors_are_scoped_to_the_selected_date_range(): void
    {
        $this->pageView($this->uuid(61), 's1', now()->subDays(20)->toDateTimeString());
        $this->pageView($this->uuid(62), 's2', now()->subDay()->toDateTimeString());

        $from = now()->subDays(3)->toDateString();
        $to = now()->toDateString();

        $report = $this->service->report($from, $to);

        $this->assertSame(1, $report['visitors']['unique'], 'the 20-day-old visitor is outside the range');
    }

    // ---------------------------------------------------------------------
    // Comparison
    // ---------------------------------------------------------------------

    public function test_previous_period_comparison_provides_visitor_metrics(): void
    {
        $this->pageView($this->uuid(71), 'current', now()->subDay()->toDateTimeString());

        $previous = $this->service->report(
            now()->subDays(13)->toDateString(),
            now()->subDays(7)->toDateString()
        )['compare']['visitors'];

        $this->assertSame(0, $previous['unique']);
        $this->assertSame(0, $previous['new']);
        $this->assertSame(0, $previous['returning']);
        $this->assertSame(0, $previous['sessions']);
    }

    // ---------------------------------------------------------------------
    // Traffic, diagnostics and privacy
    // ---------------------------------------------------------------------

    public function test_traffic_rows_report_visitors_alongside_sessions(): void
    {
        $this->pageView($this->uuid(81), 's1', now()->subDay()->toDateTimeString(), ['source' => 'google', 'medium' => 'cpc']);
        $this->pageView($this->uuid(82), 's2', now()->subDay()->toDateTimeString(), ['source' => 'google', 'medium' => 'cpc']);
        $this->pageView($this->uuid(81), 's3', now()->subDay()->toDateTimeString(), ['source' => 'google', 'medium' => 'cpc']);

        $traffic = $this->report()['traffic'];
        $row = collect($traffic['by_source'])->firstWhere('label', 'google');

        $this->assertNotNull($row);
        $this->assertSame(3, $row['views']);
        $this->assertSame(3, $row['sessions']);
        $this->assertSame(2, $row['visitors'], 'one visitor across three sessions');
    }

    public function test_diagnostics_report_visitor_tracking_status_and_coverage(): void
    {
        $this->pageView($this->uuid(91), 's1', now()->subDay()->toDateTimeString());
        // Pre-tracking history with no identity.
        $this->pageView(null, 'legacy', now()->subDays(20)->toDateTimeString());

        $tracking = $this->report()['tracking'];

        $this->assertSame('limited', $tracking['visitor_status']);
        $this->assertSame(1, $tracking['visitor_events_with_id']);
        $this->assertSame(1, $tracking['visitor_events_without_id']);
        $this->assertSame(1, $tracking['visitor_unique_all_time']);
        $this->assertSame(1, $tracking['visitor_sessions']);
        $this->assertNotNull($tracking['visitor_first_event']);
        $this->assertStringContainsString('since visitor tracking was enabled', $tracking['visitor_coverage_note']);
    }

    public function test_diagnostics_report_inactive_before_any_identified_event(): void
    {
        $tracking = $this->report()['tracking'];

        $this->assertSame('inactive', $tracking['visitor_status']);
        $this->assertSame(0, $tracking['visitor_events_with_id']);
        $this->assertStringContainsString('cannot be reported', $tracking['visitor_coverage_note']);
    }

    public function test_the_dashboard_never_renders_a_raw_visitor_id(): void
    {
        $visitor = $this->uuid(101);
        $this->pageView($visitor, 's1', now()->subDay()->toDateTimeString());

        $html = $this->getAnalyticsHtml();

        $this->assertStringContainsString('Website Visitors', $html);
        $this->assertStringNotContainsString($visitor, $html, 'a raw visitor_id must never be exposed');
    }

    public function test_the_dashboard_renders_the_visitor_kpis_and_coverage_note(): void
    {
        $this->pageView($this->uuid(102), 's1', now()->subDay()->toDateTimeString());

        $html = $this->getAnalyticsHtml();

        $this->assertStringContainsString('Website Visitors', $html);
        $this->assertStringContainsString('New Visitors', $html);
        $this->assertStringContainsString('Returning Visitors', $html);
        $this->assertStringContainsString('Visitor Trends', $html);
        $this->assertStringContainsString('New Visitor Rate', $html);
        $this->assertStringContainsString('Returning Visitor Rate', $html);
        $this->assertStringContainsString('since visitor tracking was enabled', $html);
    }

    public function test_the_visitor_id_is_never_pushed_to_meta_capi(): void
    {
        $visitor = $this->uuid(103);
        $this->pageView($visitor, 's1', now()->subDay()->toDateTimeString(), ['payload' => ['utm_source' => 'google']]);

        $row = AnalyticsEvent::firstWhere('visitor_id', $visitor);

        $this->assertNotNull($row);
        $this->assertArrayNotHasKey('visitor_id', (array) $row->payload);
        $this->assertStringNotContainsString(
            $visitor,
            (string) json_encode(DB::table('analytics_conversions')->get()),
            'visitor_id must not leak into the Meta CAPI ledger'
        );
    }

    public function test_the_existing_events_visitors_session_metric_is_preserved(): void
    {
        $visitor = $this->uuid(111);
        $at = now()->subDays(2)->toDateTimeString();

        $this->pageView($visitor, 's1', $at);
        $this->pageView($visitor, 's2', $at);
        // A pre-migration session with no identity still counts historically.
        $this->pageView(null, 's3', $at);

        $report = $this->report();

        $this->assertSame(3, $report['events']['visitors'], 'events.visitors remains distinct sessions');
        $this->assertSame(1, $report['visitors']['unique'], 'visitors.unique is the visitor-based measure');
    }

    public function test_a_failing_visitor_identity_never_breaks_event_recording(): void
    {
        $request = Request::create('/');
        $request->cookies->set(VisitorIdentity::COOKIE_NAME, 'malformed');

        $throwing = new class extends VisitorIdentity
        {
            public function resolve(?Request $request = null): ?string
            {
                throw new \RuntimeException('cookie storage unavailable');
            }
        };

        $recorder = new AnalyticsEventRecorder(
            app(EcommerceDataService::class),
            $throwing,
        );

        $recorder->pageView($request);

        $event = AnalyticsEvent::where('event_type', AnalyticsEvent::PAGE_VIEW)->latest('id')->first();

        $this->assertNotNull($event, 'the event must still be recorded');
        $this->assertNull($event->visitor_id);
    }

    public function test_caching_still_works_with_the_visitor_section(): void
    {
        $this->pageView($this->uuid(121), 's1', now()->subDay()->toDateTimeString());

        $first = $this->report();
        $second = $this->report();

        $this->assertSame($first, $second, 'a warm cache returns an identical structure');
        $this->assertSame(1, $second['visitors']['unique']);
    }

    public function test_the_cache_version_is_bumped_so_old_payloads_are_evicted(): void
    {
        $this->assertNotSame('18', AnalyticsCommandCenterService::VERSION);
    }

    // ---------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------

    protected function report(): array
    {
        return $this->service->report(
            now()->subDays(6)->toDateString(),
            now()->toDateString()
        );
    }

    protected function getAnalyticsHtml(): string
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $admin = Admin::factory()->create();
        $admin->roles()->attach(Role::where('slug', 'manager')->firstOrFail());

        $response = $this->actingAs($admin, 'admin')->get('/admin/analytics');

        $response->assertOk();

        return $response->getContent();
    }
}
