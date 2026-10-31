<?php

namespace App\Services\Analytics;

use App\Consent\ConsentService;
use App\Consent\Enums\ConsentCategory;
use App\Models\AnalyticsConversion;
use App\Models\Order;
use App\Services\Analytics\Exceptions\OpenAiTimestampException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Server-side delivery of the OpenAI order_created conversion via Conversions API.
 *
 * Dedicated to OpenAI ChatGPT Ads. Communicates directly with https://bzr.openai.com/v1/events?pid=PIXEL_ID.
 * Independent of Meta CAPI. State is kept on the durable analytics_conversions ledger.
 */
class OpenAiCapiService
{
    /**
     * OpenAI conversion attribution window: conversions must have occurred within the past 7 days.
     */
    public const MAX_TIMESTAMP_AGE_MS = 604800000; // 7 * 86,400 * 1,000 ms

    /**
     * Permitted future clock skew tolerance: at most 10 minutes in the future.
     */
    public const MAX_FUTURE_SKEW_MS = 600000; // 10 * 60 * 1,000 ms
    public function __construct(
        protected EcommerceDataService $ecommerce,
        protected OpenAiEcommercePayloadService $payloadService,
        protected ConsentService $consentService,
    ) {}

    /**
     * Deterministic, order-scoped event ID shared between the browser pixel and
     * the server CAPI for the same order conversion.
     */
    public function eventId(Order $order): string
    {
        return hash_hmac(
            'sha256',
            'openai-capi-order:'.$order->order_number,
            (string) config('app.key'),
        );
    }

    /**
     * Authoritative non-sensitive configuration diagnostics.
     */
    public function configuration(): array
    {
        $key = $this->capiKey();
        $keyPresent = $key !== '';

        $requirements = [
            'env_enabled' => (bool) config('openai_ads.enabled', false),
            'pixel_present' => $this->pixelId() !== '',
            'key_present' => $keyPresent,
        ];

        $missing = array_keys(array_filter(
            $requirements,
            static fn (bool $satisfied): bool => ! $satisfied
        ));

        return [
            'configured' => $missing === [],
            'requirements' => $requirements,
            'missing' => $missing,
            'pixel_id' => $this->pixelId(),
            'key_present' => $keyPresent,
        ];
    }

    public function isConfigured(): bool
    {
        return $this->configuration()['configured'];
    }

    public function maxAttempts(): int
    {
        return max(1, (int) config('openai_ads.max_attempts', 3));
    }

    public function pixelId(): string
    {
        return trim((string) (setting('openai_pixel_id') ?: config('openai_ads.pixel_id', '')));
    }

    public function capiKey(): string
    {
        return trim((string) (secret_setting('openai_capi_key') ?: config('openai_ads.capi_key', '')));
    }

    public function endpoint(): string
    {
        $base = rtrim(config('openai_ads.endpoint', 'https://bzr.openai.com/v1/events'), '/');

        return $base.'?pid='.$this->pixelId();
    }

    /**
     * Resolve and strictly validate the event timestamp in Unix milliseconds.
     *
     * Rules:
     * - Preserve the original order event timestamp when it falls within OpenAI's permitted 7-day window.
     * - Reject events older than seven days as expired (throws OpenAiTimestampException['expired']).
     * - Reject events more than ten minutes in the future (throws OpenAiTimestampException['future']).
     * - Never silently turn an old conversion into a new conversion by assigning the current time.
     *
     * @throws OpenAiTimestampException
     */
    public function resolveTimestampMs(Order $order, array $context = []): int
    {
        $nowMs = (int) ($context['reference_now_ms'] ?? round(microtime(true) * 1000));
        $sevenDaysAgoMs = $nowMs - self::MAX_TIMESTAMP_AGE_MS;
        $futureLimitMs = $nowMs + self::MAX_FUTURE_SKEW_MS;

        if (isset($context['timestamp_ms'])) {
            $timestampMs = (int) $context['timestamp_ms'];
        } elseif ($order->created_at) {
            $timestampMs = (int) ($order->created_at->getTimestamp() * 1000);
        } else {
            $timestampMs = $nowMs;
        }

        if ($timestampMs < $sevenDaysAgoMs) {
            throw new OpenAiTimestampException(
                "OpenAI CAPI: Event timestamp ({$timestampMs}) is older than 7 days (boundary: {$sevenDaysAgoMs}). Conversion expired.",
                'expired'
            );
        }

        if ($timestampMs > $futureLimitMs) {
            throw new OpenAiTimestampException(
                "OpenAI CAPI: Event timestamp ({$timestampMs}) is more than 10 minutes in the future (boundary: {$futureLimitMs}). Conversion rejected.",
                'future'
            );
        }

        return $timestampMs;
    }

    /**
     * Build the standard OpenAI conversion event payload.
     *
     * Official schema:
     * {
     *   "id": "unique_event_id",
     *   "type": "order_created",
     *   "timestamp_ms": 1793505600000,
     *   "oppref": "original_oppref",
     *   "source_url": "https://vanriti.com/checkout/success",
     *   "action_source": "web",
     *   "user": {
     *     "obref": "optional_unchanged_browser_reference",
     *     "email_sha256": "optional_lowercase_sha256_hash"
     *   },
     *   "data": {
     *     "type": "contents",
     *     "amount": 149900,
     *     "currency": "INR",
     *     "contents": []
     *   }
     * }
     */
    public function buildPayload(Order $order, array $context = []): array
    {
        $order->loadMissing(['items', 'user']);

        $consentGranted = array_key_exists('consent_granted', $context)
            ? (bool) $context['consent_granted']
            : $this->consentService->resolve()->allows(ConsentCategory::Advertising);

        $user = [];

        // Forward original oppref click reference when available
        $oppref = $order->oppref ?? ($context['oppref'] ?? null);

        // Only include browser reference (obref) and user identifiers when measurement/advertising consent is granted
        if ($consentGranted) {
            $obref = trim((string) ($context['obref'] ?? request()->cookie('__obref', '')));
            if ($obref !== '') {
                $user['obref'] = $obref;
            }

            $orderUser = $order->user;
            $email = $orderUser?->email;
            if (is_string($email) && $email !== '') {
                $user['email_sha256'] = hash('sha256', strtolower(trim($email)));
            }
        }

        $data = $this->payloadService->orderCreated($order);
        $timestampMs = $this->resolveTimestampMs($order, $context);
        $sourceUrl = (string) ($context['source_url'] ?? route('checkout.success', $order));

        $event = [
            'id' => $this->eventId($order),
            'type' => 'order_created',
            'timestamp_ms' => $timestampMs,
            'source_url' => $sourceUrl,
            'action_source' => 'web',
            'data' => $data,
        ];

        // Omit the user property entirely when no permitted identifiers are available
        if (! empty($user)) {
            $event['user'] = $user;
        }

        if (is_string($oppref) && $oppref !== '') {
            $event['oppref'] = $oppref;
        }

        return $event;
    }

    /**
     * Deliver the OpenAI order_created conversion for an eligible order.
     * Guaranteed safe: never throws into an HTTP request.
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
            ->where('event_type', 'order_created')
            ->where('order_number', $order->order_number)
            ->first();

        if (! $ledger) {
            $ledger = app(ConversionService::class)->record(
                'order_created',
                $order->order_number,
                $this->payloadService->orderCreated($order),
                $order->attribution_source ?: 'chatgpt_ads'
            );
        }

        // Terminal states check: once sent, expired, or rejected, do not retry
        if (in_array($ledger->openai_state, ['sent', 'expired', 'rejected'], true)
            || (int) $ledger->openai_attempts >= $this->maxAttempts()) {
            return;
        }

        try {
            $this->sendOrder($order, $context);

            $ledger->update([
                'openai_state' => 'sent',
                'openai_sent_at' => now(),
                'openai_attempts' => (int) $ledger->openai_attempts + 1,
                'openai_event_id' => $this->eventId($order),
            ]);
        } catch (OpenAiTimestampException $e) {
            $terminalState = $e->isExpired() ? 'expired' : 'rejected';

            // Mark as terminal expired/rejected state so it cannot be retried indefinitely
            $ledger->update([
                'openai_state' => $terminalState,
                'openai_sent_at' => null,
                'openai_attempts' => (int) $ledger->openai_attempts + 1,
                'openai_event_id' => $this->eventId($order),
            ]);

            Log::warning("OpenAI CAPI: order_created delivery {$terminalState} due to timestamp restriction", [
                'order' => $order->order_number,
                'event_id' => $this->eventId($order),
                'reason' => $e->getMessage(),
            ]);
        } catch (\Throwable $e) {
            $attempts = (int) $ledger->openai_attempts + 1;
            $ledger->update([
                'openai_state' => 'failed',
                'openai_sent_at' => null,
                'openai_attempts' => $attempts,
                'openai_event_id' => $this->eventId($order),
            ]);

            Log::error('OpenAI CAPI: order_created delivery failed', [
                'order' => $order->order_number,
                'event_id' => $this->eventId($order),
                'attempt' => $attempts,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send HTTP POST to OpenAI Conversions API endpoint.
     */
    public function sendOrder(Order $order, array $context = []): Response
    {
        $payload = $this->buildPayload($order, $context);

        $validateOnly = array_key_exists('validate_only', $context)
            ? (bool) $context['validate_only']
            : (bool) config('openai_ads.validate_only', false);

        $body = [
            'validate_only' => $validateOnly,
            'events' => [$payload],
        ];

        $response = Http::asJson()
            ->withToken($this->capiKey())
            ->connectTimeout((int) config('openai_ads.connect_timeout', 3))
            ->timeout((int) config('openai_ads.timeout', 5))
            ->post($this->endpoint(), $body);

        if ($response->failed()) {
            throw new \RuntimeException($this->responseError($response));
        }

        Log::info('OpenAI CAPI: order_created delivered', [
            'order' => $order->order_number,
            'event_id' => $payload['id'],
            'validate_only' => $validateOnly,
            'status' => $response->status(),
        ]);

        return $response;
    }

    protected function responseError(Response $response): string
    {
        $json = $response->json();
        $message = is_array($json) ? trim((string) ($json['error']['message'] ?? $json['message'] ?? '')) : '';
        $code = is_array($json) ? (int) ($json['error']['code'] ?? $response->status()) : $response->status();

        return 'OpenAI CAPI error (HTTP '.$response->status()
            .($code ? ', code '.$code : '')
            .($message !== '' ? ', '.substr($message, 0, 200) : '')
            .')';
    }
}

