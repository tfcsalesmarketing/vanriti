<?php

namespace App\Services\Analytics;

use App\Models\AnalyticsConversion;
use App\Models\Order;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Server-side delivery of the Meta Purchase conversion via Conversions API.
 *
 * This is the ONLY component that may access the CAPI access token. It never
 * renders the token to Blade/JavaScript, and delivery state lives on the
 * durable 6G ledger row so retries are deterministic and idempotent.
 *
 * The endpoint is fixed to graph.facebook.com; neither the token nor the pixel
 * id are user-controllable at request time, and every outbound request has
 * bounded connect/request timeouts.
 */
class MetaCapiService
{
    protected const GRAPH_BASE = 'https://graph.facebook.com';

    public function __construct(
        protected EcommerceDataService $ecommerce,
    ) {
    }

    /**
     * Deterministic, order-scoped Meta event_id shared by the browser Pixel
     * Purchase and the server CAPI Purchase for the same order. Derived from
     * the canonical order identity (order_number) via HMAC-SHA256 so it is
     * stable across page refreshes and never exposes database identifiers.
     */
    public function eventId(Order $order): string
    {
        return hash_hmac(
            'sha256',
            'meta-capi-purchase:'.$order->order_number,
            (string) config('app.key'),
        );
    }

    public function isConfigured(): bool
    {
        return (bool) config('meta.enabled', false)
            && $this->pixelId() !== ''
            && $this->accessToken() !== '';
    }

    public function maxAttempts(): int
    {
        return max(1, (int) config('meta.max_attempts', 3));
    }

    /**
     * Deliver the Meta Purchase conversion for an eligible order whose ledger
     * row says it has not already been delivered. Never throws in a way that
     * can survive into a synchronous request: on failure it records the state
     * and only rethrows when running on an async queue with attempts left.
     */
    public function deliver(Order $order, array $context = []): void
    {
        if (! $this->isConfigured()) {
            return;
        }

        if (! $this->ecommerce->purchaseEligible($order)) {
            return;
        }

        $ledger = AnalyticsConversion::query()
            ->where('event_type', 'purchase')
            ->where('order_number', $order->order_number)
            ->first();

        if (! $ledger) {
            return;
        }

        if ($ledger->meta_state === 'sent' || (int) $ledger->meta_attempts >= $this->maxAttempts()) {
            return;
        }

        try {
            $this->sendPurchase($order, $context);

            $ledger->update([
                'meta_state' => 'sent',
                'meta_sent_at' => now(),
                'meta_attempts' => (int) $ledger->meta_attempts + 1,
            ]);
        } catch (\Throwable $e) {
            $attempts = (int) $ledger->meta_attempts + 1;
            $ledger->update([
                'meta_state' => 'failed',
                'meta_sent_at' => null,
                'meta_attempts' => $attempts,
            ]);

            Log::error('Meta CAPI: Purchase delivery failed', [
                'order' => $order->order_number,
                'event_id' => $this->eventId($order),
                'attempt' => $attempts,
                'error' => $e->getMessage(),
            ]);

            // Retry only on an async queue; a synchronous (test) or exhausted
            // attempt must never fail the checkout request.
            if (config('queue.default') !== 'sync' && $attempts < $this->maxAttempts()) {
                throw $e;
            }
        }
    }

    /**
     * Perform the actual HTTP delivery. Throws a sanitized RuntimeException on
     * failure; the message contains only HTTP status/error envelope data.
     */
    public function sendPurchase(Order $order, array $context = []): void
    {
        $order->loadMissing(['items', 'user']);

        $payload = $this->buildPayload($order, $context);

        $body = ['data' => [$payload]];

        // Meta's Test Events tool issues a code; while set, it routes events to
        // the Test Events view instead of production reporting.
        $testEventCode = trim((string) ($context['test_event_code'] ?? ''));
        if ($testEventCode !== '') {
            $body['test_event_code'] = $testEventCode;
        }

        $response = Http::asJson()
            ->connectTimeout((int) config('meta.connect_timeout', 3))
            ->timeout((int) config('meta.timeout', 5))
            ->post($this->endpoint().'?access_token='.$this->accessToken(), $body);

        if ($response->failed()) {
            throw new \RuntimeException($this->responseError($response));
        }

        Log::info('Meta CAPI: Purchase delivered', [
            'order' => $order->order_number,
            'event_id' => $payload['event_id'],
            'events_received' => $response->json('events_received', 0),
        ]);
    }

    /**
     * Full Meta Purchase payload for CAPI. user_data is normalized and
     * SHA-256 hashed exactly where Meta requires hashing; the access token is
     * never part of the body.
     */
    public function buildPayload(Order $order, array $context = []): array
    {
        $user = $order->user;
        $userData = [];

        $email = $user?->email;
        if (is_string($email) && $email !== '') {
            $userData['em'] = [hash('sha256', strtolower(trim($email)))];
        }

        $phone = $this->normalizePhone((string) ($order->billing_mobile ?: ($user?->phone ?? '')));
        if ($phone !== null) {
            $userData['ph'] = [hash('sha256', $phone)];
        }

        if ($order->user_id) {
            $userData['external_id'] = [hash('sha256', (string) $order->user_id)];
        }

        $customData = [
            'currency' => 'INR',
            'value' => (float) $order->grand_total,
            'content_type' => 'product',
            'contents' => $order->items
                ->map(fn ($item) => [
                    'id' => (string) $item->sku,
                    'quantity' => (int) $item->quantity,
                    'item_price' => (float) $item->unit_price,
                    'delivery_category' => (string) config('meta.delivery_category', 'home_delivery'),
                ])
                ->values()
                ->all(),
            'transaction_id' => (string) $order->order_number,
        ];

        // v26 website-event required/recommended customer information. The
        // client IP and user agent are the browser's own, forwarded raw (do
        // not hash), exactly as Meta's schema expects.
        foreach (['client_ip_address', 'client_user_agent', 'fbp', 'fbc'] as $key) {
            $value = trim((string) ($context[$key] ?? ''));
            if ($value !== '') {
                $userData[$key] = $value;
            }
        }

        $event = [
            'event_name' => 'Purchase',
            'event_time' => (int) ($context['event_time'] ?? now()->getTimestamp()),
            'event_id' => $this->eventId($order),
            'action_source' => 'website',
            'event_source_url' => (string) ($context['event_source_url'] ?? route('checkout.success', $order)),
            'user_data' => $userData,
            'custom_data' => $customData,
        ];

        return $event;
    }

    protected function endpoint(): string
    {
        $version = ltrim((string) config('meta.api_version', '21.0'), 'v');

        return self::GRAPH_BASE.'/v'.$version.'/'.$this->pixelId().'/events';
    }

    protected function pixelId(): string
    {
        return trim((string) setting('meta_pixel_id', ''));
    }

    protected function accessToken(): string
    {
        return trim((string) secret_setting('meta_capi_access_token', ''));
    }

    /**
     * Normalize an Indian phone to E.164 digits (example: 91XXXXXXXXXX) for
     * Meta's SHA-256 hashing requirements. Strips symbols, letters and any
     * leading domestic-trunk zeros; returns null when unresolvable.
     */
    protected function normalizePhone(string $raw): ?string
    {
        $digits = preg_replace('/\D+/', '', $raw) ?? '';

        if ($digits === '') {
            return null;
        }

        // Domestic trunk prefix: 09876543210 => 9876543210.
        $digits = ltrim($digits, '0');

        if ($digits === '') {
            return null;
        }

        if (strlen($digits) === 10) {
            $digits = '91'.$digits;
        } elseif (! (str_starts_with($digits, '91') && strlen($digits) === 12)) {
            return null;
        }

        return $digits;
    }

    /**
     * Build a sanitized failure message from the Meta error envelope. Never
     * includes the access token, request body, or raw customer identifiers.
     */
    protected function responseError(Response $response): string
    {
        $json = $response->json();
        $message = is_array($json) ? trim((string) ($json['error']['message'] ?? '')) : '';
        $code = is_array($json) ? (int) ($json['error']['code'] ?? 0) : 0;

        return 'Meta CAPI error (HTTP '.$response->status()
            .($code ? ', code '.$code : '')
            .($message !== '' ? ', '.substr($message, 0, 200) : '')
            .')';
    }
}