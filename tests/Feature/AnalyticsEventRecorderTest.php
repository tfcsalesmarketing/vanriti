<?php

namespace Tests\Feature;

use App\Http\Middleware\TrackStorefrontPageView;
use App\Models\AnalyticsEvent;
use App\Models\Product;
use App\Services\Analytics\AnalyticsEventRecorder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

class AnalyticsEventRecorderTest extends TestCase
{
    use RefreshDatabase;

    protected AnalyticsEventRecorder $recorder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SettingsSeeder::class);

        $this->recorder = app(AnalyticsEventRecorder::class);
    }

    protected function makeProduct(string $sku = 'REC-001', float $price = 100.0): Product
    {
        return Product::factory()->create([
            'sku' => $sku,
            'selling_price' => $price,
            'mrp' => $price,
            'stock' => 25,
            'status' => 'active',
        ]);
    }

    public function test_session_id_is_a_stable_hmac_never_the_raw_session_id(): void
    {
        $id = AnalyticsEventRecorder::sessionId();

        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $id);
        $this->assertSame($id, AnalyticsEventRecorder::sessionId());
        $this->assertSame(hash_hmac('sha256', 'analytics-session:'.session()->getId(), (string) config('app.key')), $id);
    }

    public function test_page_view_captures_utm_acquisition_and_locks_landing_path(): void
    {
        $first = Request::create('/products/snapback', 'GET', [
            'utm_source' => 'newsletter',
            'utm_medium' => 'email',
            'utm_campaign' => 'launch',
        ], [], [], ['HTTP_USER_AGENT' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X)']);

        $this->recorder->pageView($first);

        $event = AnalyticsEvent::where('event_type', AnalyticsEvent::PAGE_VIEW)->latest('id')->first();
        $this->assertNotNull($event);
        $this->assertSame('newsletter', $event->source);
        $this->assertSame('email', $event->medium);
        $this->assertSame('launch', $event->campaign);
        $this->assertSame('mobile', $event->device_type);
        $this->assertSame('products/snapback', $event->landing_path);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $event->session_id);

        // a later page keeps the session's original landing path
        $second = Request::create('/cart', 'GET', [], [], [], ['HTTP_USER_AGENT' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X)']);
        $this->recorder->pageView($second);

        $latest = AnalyticsEvent::where('event_type', AnalyticsEvent::PAGE_VIEW)->latest('id')->first();
        $this->assertSame('products/snapback', $latest->landing_path);
        $this->assertSame(2, AnalyticsEvent::where('event_type', AnalyticsEvent::PAGE_VIEW)->count());
    }

    public function test_page_view_uses_referrer_host_as_source_and_referral_medium(): void
    {
        $request = Request::create('/shop', 'GET', [], [], [], [
            'HTTP_REFERER' => 'https://www.google.com/search?q=vanriti',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
        ]);

        $this->recorder->pageView($request);

        $event = AnalyticsEvent::where('event_type', AnalyticsEvent::PAGE_VIEW)->latest('id')->first();
        $this->assertSame('www.google.com', $event->source);
        $this->assertSame('referral', $event->medium);
        $this->assertSame('desktop', $event->device_type);
        $this->assertNull($event->campaign);
    }

    public function test_page_view_skips_bots(): void
    {
        $request = Request::create('/shop', 'GET', [], [], [], ['HTTP_USER_AGENT' => 'Googlebot/2.1 (+http://www.google.com/bot.html)']);

        $this->recorder->pageView($request);

        $this->assertSame(0, AnalyticsEvent::where('event_type', AnalyticsEvent::PAGE_VIEW)->count());
    }

    public function test_view_item_records_sku_product_and_payload(): void
    {
        $product = $this->makeProduct('VIEW-001', 149.0);

        $this->recorder->viewItem($product);

        $event = AnalyticsEvent::where('event_type', AnalyticsEvent::VIEW_ITEM)->first();
        $this->assertNotNull($event);
        $this->assertSame('VIEW-001', $event->sku);
        $this->assertSame($product->id, $event->product_id);
        $this->assertSame('view_item', $event->payload['event']);
    }

    public function test_add_to_cart_records_quantity_and_sku(): void
    {
        $product = $this->makeProduct('CART-001', 99.0);

        $this->recorder->addToCart($product, null, 3);

        $event = AnalyticsEvent::where('event_type', AnalyticsEvent::ADD_TO_CART)->first();
        $this->assertNotNull($event);
        $this->assertSame('CART-001', $event->sku);
        $this->assertSame(3, $event->quantity);
        $this->assertSame('add_to_cart', $event->payload['event']);
    }

    public function test_middleware_records_storefront_get_and_skips_admin_and_non_get(): void
    {
        $middleware = app(TrackStorefrontPageView::class);
        $next = fn () => new Response('', 200);

        $middleware->handle(Request::create('/shop', 'GET'), $next);
        $this->assertSame(1, AnalyticsEvent::where('event_type', AnalyticsEvent::PAGE_VIEW)->count());

        $middleware->handle(Request::create('/admin/analytics', 'GET'), $next);
        $this->assertSame(1, AnalyticsEvent::where('event_type', AnalyticsEvent::PAGE_VIEW)->count());

        $middleware->handle(Request::create('/shop', 'POST'), $next);
        $this->assertSame(1, AnalyticsEvent::where('event_type', AnalyticsEvent::PAGE_VIEW)->count());

        $middleware->handle(Request::create('/up', 'GET'), $next);
        $this->assertSame(1, AnalyticsEvent::where('event_type', AnalyticsEvent::PAGE_VIEW)->count());
    }
}
