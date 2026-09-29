<?php

namespace App\Services\Analytics;

use App\Models\AnalyticsEvent;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * First-party behavioral event recorder for the analytics dashboard.
 *
 * Records storefront events into analytics_events using the exact payload
 * shapes already produced by {@see EcommerceDataService} so no second
 * tracking/event vocabulary is introduced. This service is fire-and-forget:
 * recording never throws, never fails a request, and never renders to HTML.
 *
 * Identity is a salted HMAC of the Laravel session id; no customer PII is ever
 * stored. Each event also carries the persistent anonymous visitor_id (see
 * {@see VisitorIdentity}) so the dashboard can count unique/new/returning
 * visitors, which a per-session identifier cannot express. Only page_view
 * carries acquisition context (UTM / referrer / device / landing path). Order,
 * revenue and payment truth remains in the application database — this is
 * measurement, not the source of truth.
 */
class AnalyticsEventRecorder
{
    public function __construct(
        protected EcommerceDataService $ecommerce,
        protected VisitorIdentity $visitor,
    ) {}

    /**
     * Record a storefront page view, capturing acquisition context and the
     * session's landing path. Bot traffic is never recorded.
     */
    public function pageView(Request $request): void
    {
        if ($this->isBot($request->userAgent())) {
            return;
        }

        $path = $request->path();
        $landing = session('analytics.landing');
        if ($landing === null) {
            $landing = $path;
            session(['analytics.landing' => $landing]);
        }

        $referrer = $request->headers->get('referer');
        $referrerHost = null;
        if ($referrer !== null && $referrer !== '') {
            $host = parse_url($referrer, PHP_URL_HOST);
            $referrerHost = is_string($host) && $host !== '' ? $host : null;
        }

        $this->record(AnalyticsEvent::PAGE_VIEW, [
            'sku' => null,
            'product_id' => null,
            'quantity' => null,
            'source' => $request->query('utm_source') ?: $referrerHost,
            'medium' => $request->query('utm_medium') ?: ($referrerHost !== null ? 'referral' : 'direct'),
            'campaign' => $request->query('utm_campaign'),
            'device_type' => $this->deviceType($request->userAgent()),
            'landing_path' => $landing,
            'payload' => [
                'path' => $path,
                'method' => $request->method(),
            ],
        ], $request);
    }

    /**
     * Record a product detail page view (view_item) for the initially selected
     * variant, matching the payload fired to GA4/Meta on the same page.
     */
    public function viewItem(Product $product, ?ProductVariant $variant = null): void
    {
        try {
            $payload = $this->ecommerce->viewItem($product, $variant, 1);
        } catch (\Throwable $e) {
            $this->logFailure('view_item', $e);

            return;
        }

        $this->record(AnalyticsEvent::VIEW_ITEM, [
            'sku' => $this->itemSku($payload['ecommerce']['items'][0] ?? []),
            'product_id' => $product->id,
            'quantity' => (int) ($payload['ecommerce']['items'][0]['quantity'] ?? 1),
            'payload' => $payload,
        ]);
    }

    /**
     * Record a successful cart addition (add_to_cart / Buy Now flow).
     */
    public function addToCart(Product $product, ?ProductVariant $variant, int $quantity): void
    {
        try {
            $payload = $this->ecommerce->addToCart($product, $variant, $quantity);
        } catch (\Throwable $e) {
            $this->logFailure('add_to_cart', $e);

            return;
        }

        $this->record(AnalyticsEvent::ADD_TO_CART, [
            'sku' => $this->itemSku($payload['ecommerce']['items'][0] ?? []),
            'product_id' => $product->id,
            'quantity' => (int) $payload['ecommerce']['items'][0]['quantity'],
            'payload' => $payload,
        ]);
    }

    /**
     * Record a checkout-page render (begin_checkout). One row is written per
     * cart SKU so the product report can attribute checkout intent to items.
     */
    public function beginCheckout(Cart $cart): void
    {
        try {
            $payload = $this->ecommerce->beginCheckout($cart);
        } catch (\Throwable $e) {
            $this->logFailure('begin_checkout', $e);

            return;
        }

        $this->recordItems(AnalyticsEvent::BEGIN_CHECKOUT, $payload);
    }

    /**
     * Record payment-method initiation for an order (COD confirmation or the
     * Razorpay checkout token creation). Kept separate from the business
     * "Payment Started" funnel stage, which is computed from the payments table.
     */
    public function addPaymentInfo(Order $order, string $paymentType): void
    {
        try {
            $payload = $this->ecommerce->addPaymentInfo($order, $paymentType);
        } catch (\Throwable $e) {
            $this->logFailure('add_payment_info', $e);

            return;
        }

        $this->recordItems(AnalyticsEvent::ADD_PAYMENT_INFO, $payload);
    }

    /**
     * Write one analytics_event per item SKU found in a multi-item payload.
     */
    protected function recordItems(string $eventType, array $payload): void
    {
        $items = $payload['ecommerce']['items'] ?? [];

        foreach ($items as $item) {
            $this->record($eventType, [
                'sku' => $this->itemSku($item),
                'product_id' => null,
                'quantity' => (int) ($item['quantity'] ?? 1),
                'payload' => $payload,
            ]);
        }
    }

    protected function record(string $eventType, array $attributes, ?Request $request = null): void
    {
        try {
            AnalyticsEvent::create(array_merge([
                'event_type' => $eventType,
                'session_id' => self::sessionId(),
                'visitor_id' => $this->visitorId($request),
                'user_id' => auth('web')->id(),
                'source' => null,
                'medium' => null,
                'campaign' => null,
                'device_type' => null,
                'landing_path' => null,
                'payload' => null,
                'occurred_at' => now(),
            ], $attributes));
        } catch (\Throwable $e) {
            $this->logFailure($eventType, $e);
        }
    }

    /**
     * Salted hash of the underlying session identifier; never stored raw.
     */
    public static function sessionId(): string
    {
        $id = session()->getId() ?: 'no-session';

        return hash_hmac('sha256', 'analytics-session:'.$id, (string) config('app.key'));
    }

    /**
     * Persistent anonymous visitor id, or null when one cannot be resolved
     * (no browser request, rejected cookie, storage failure). A missing
     * visitor_id is legitimate and must never fail the commerce operation that
     * triggered the event.
     */
    protected function visitorId(?Request $request = null): ?string
    {
        try {
            return $this->visitor->resolve($request);
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function itemSku(array $item): ?string
    {
        $sku = $item['item_id'] ?? null;

        return is_string($sku) && $sku !== '' ? $sku : null;
    }

    protected function deviceType(?string $userAgent): string
    {
        $ua = mb_strtolower((string) $userAgent);

        if (str_contains($ua, 'ipad')
            || str_contains($ua, 'tablet')
            || str_contains($ua, 'kindle')
            || str_contains($ua, 'silk')) {
            return 'tablet';
        }

        if (str_contains($ua, 'mobile')
            || str_contains($ua, 'android')
            || str_contains($ua, 'iphone')
            || str_contains($ua, 'ipod')
            || str_contains($ua, 'windows phone')) {
            return 'mobile';
        }

        return 'desktop';
    }

    protected function isBot(?string $userAgent): bool
    {
        $ua = mb_strtolower((string) $userAgent);

        return $ua === '' || preg_match(
            '/bot|crawler|spider|slurp|curl|wget|python|headless|facebookexternalhit|preview|scrape|monitor|uptime|healthcheck|crawling/i',
            $ua,
        ) === 1;
    }

    protected function logFailure(string $eventType, \Throwable $e): void
    {
        Log::warning('[analytics] event recording failed', [
            'event_type' => $eventType,
            'message' => $e->getMessage(),
        ]);
    }
}
