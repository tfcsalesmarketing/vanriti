<?php

namespace App\Services\Analytics;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;

/**
 * First-party attribution service for ChatGPT Ads traffic.
 *
 * Captures and persists the official ChatGPT click reference `oppref`
 * and optional campaign parameters across navigation, guest carts, login,
 * and checkout.
 *
 * Privacy & Safety contract:
 *  - Stores only opaque click and campaign identifiers. Never stores customer PII.
 *  - Preserves first-touch and last-touch attribution without letting unrelated
 *    later traffic overwrite existing attribution.
 *  - Uses its own first-party cookie (`vanriti_oppref`) and never modifies or
 *    overwrites the OpenAI SDK's own `__oppref` or `__obref` cookies.
 *  - Best effort: errors are logged and never interrupt the user's shopping journey.
 */
class ChatGptAdsAttributionService
{
    public const COOKIE_NAME = 'vanriti_oppref';

    public const COOKIE_DAYS = 30;

    /**
     * Inspect incoming request and capture ChatGPT Ads attribution if present.
     */
    public function capture(Request $request): ?array
    {
        try {
            $oppref = $this->sanitizeParam($request->query('oppref'));

            // If oppref is not in query, check if already in session or cookie
            if ($oppref === null) {
                return $this->resolve($request);
            }

            $source = $this->sanitizeParam($request->query('utm_source')) ?: 'chatgpt';
            $medium = $this->sanitizeParam($request->query('utm_medium')) ?: 'cpc';
            $campaign = $this->sanitizeParam($request->query('utm_campaign'));

            $now = now()->toIso8601String();

            $existing = session('chatgpt_ads.first');

            $firstTouch = $existing ?: [
                'oppref' => $oppref,
                'source' => $source,
                'medium' => $medium,
                'campaign' => $campaign,
                'captured_at' => $now,
            ];

            $lastTouch = [
                'oppref' => $oppref,
                'source' => $source,
                'medium' => $medium,
                'campaign' => $campaign,
                'captured_at' => $now,
            ];

            session([
                'chatgpt_ads.first' => $firstTouch,
                'chatgpt_ads.last' => $lastTouch,
                'chatgpt_ads.active_oppref' => $oppref,
                'chatgpt_ads.active_source' => $source,
                'chatgpt_ads.active_campaign' => $campaign,
                'chatgpt_ads.active_medium' => $medium,
            ]);

            $this->queueCookie($request, $oppref);

            return $lastTouch;
        } catch (\Throwable $e) {
            Log::warning('[analytics] ChatGPT Ads attribution capture failed', ['message' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Resolve current active attribution from session or fallback cookie.
     */
    public function resolve(?Request $request = null): ?array
    {
        try {
            $request ??= request();

            $activeOppref = session('chatgpt_ads.active_oppref');

            if ($activeOppref) {
                return [
                    'oppref' => $activeOppref,
                    'source' => session('chatgpt_ads.active_source', 'chatgpt'),
                    'campaign' => session('chatgpt_ads.active_campaign'),
                    'medium' => session('chatgpt_ads.active_medium', 'cpc'),
                    'first' => session('chatgpt_ads.first'),
                    'last' => session('chatgpt_ads.last'),
                ];
            }

            // Fallback to first-party cookie if session expired or returning visitor
            $cookieOppref = $this->sanitizeParam($request->cookie(self::COOKIE_NAME));

            if ($cookieOppref) {
                session([
                    'chatgpt_ads.active_oppref' => $cookieOppref,
                    'chatgpt_ads.active_source' => 'chatgpt',
                    'chatgpt_ads.active_medium' => 'cpc',
                ]);

                return [
                    'oppref' => $cookieOppref,
                    'source' => 'chatgpt',
                    'campaign' => null,
                    'medium' => 'cpc',
                    'first' => null,
                    'last' => null,
                ];
            }

            return null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Get the active oppref for attaching to order or conversion.
     */
    public function currentOppref(?Request $request = null): ?string
    {
        $resolved = $this->resolve($request);

        return $resolved['oppref'] ?? null;
    }

    protected function queueCookie(Request $request, string $oppref): void
    {
        Cookie::queue(Cookie::make(
            self::COOKIE_NAME,
            $oppref,
            self::COOKIE_DAYS * 24 * 60,
            '/',
            null,
            $request->isSecure(),
            true, // HttpOnly
            false,
            'lax',
        ));
    }

    protected function sanitizeParam(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        if ($trimmed === '') {
            return null;
        }

        // Limit length to avoid abuse
        return mb_substr($trimmed, 0, 190);
    }
}

