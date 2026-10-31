<?php

namespace App\Services\Analytics;

use App\Consent\ConsentService;
use App\Consent\Enums\ConsentCategory;
use App\Models\AnalyticsConversion;
use App\Models\Order;
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

        $nowMs = (int) round(microtime(true) * 1000);
        $sevenDaysAgoMs = $nowMs - (7 * 86400 * 1000);

        if (isset($context['timestamp_ms'])) {
            $timestampMs = (int) $context['timestamp_ms'];
        } elseif ($order->created_at) {
            $orderCreatedMs = (int) ($order->created_at->getTimestamp() * 1000);
            $timestampMs = ($orderCreatedMs >= $sevenDaysAgoMs && $orderCreatedMs <= $nowMs)
                ? $orderCreatedMs
                : $nowMs;
        } else {
            $timestampMs = $nowMs;
        }

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

        if ($ledger->openai_state === 'sent' || (int) $ledger->openai_attempts >= $this->maxAttempts()) {
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

