<?php

namespace App\Services\Analytics;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Persistent anonymous first-party visitor identity.
 *
 * A random UUID4 stored in a first-party, HttpOnly cookie. It exists so the
 * admin can tell unique / new / returning *visitors* apart from *sessions*,
 * which a per-session identifier can never do.
 *
 * Privacy contract — deliberately narrow:
 *  - Random UUID. No name, email, phone, address, IP address, device or
 *    browser fingerprint contributes to the value.
 *  - Never derived from the GA4 client id, a Meta id, an advertising id or the
 *    Laravel session id.
 *  - Host-only cookie: no Domain attribute, so it is not shared across
 *    subdomains and is not a cross-site identifier.
 *  - HttpOnly, so no storefront JavaScript can read it.
 *  - Never transmitted to Meta Pixel, Meta CAPI, GA4 or Google Ads. It is a
 *    first-party aggregate field only.
 *
 * Best-effort by contract: every failure path returns null and is logged, so a
 * missing or rejected cookie can never fail a commerce operation.
 */
class VisitorIdentity
{
    public const COOKIE_NAME = 'vanriti_visitor_id';

    /** One year, so a returning visitor keeps the same identity. */
    public const COOKIE_DAYS = 365;

    /**
     * Memoised for the lifetime of this instance (one per request), so a
     * request that records several events resolves the identity once and
     * queues exactly one Set-Cookie header.
     */
    protected ?string $resolved = null;

    protected bool $attempted = false;

    /**
     * Resolve the current visitor id, minting and queuing a cookie when absent.
     *
     * Returns null — never throws — when a browser request is unavailable or
     * the cookie cannot be written.
     */
    public function resolve(?Request $request = null): ?string
    {
        if ($this->attempted) {
            return $this->resolved;
        }

        $this->attempted = true;

        try {
            $request ??= request();

            $existing = $request->cookie(self::COOKIE_NAME);
            if (is_string($existing) && Str::isUuid($existing)) {
                return $this->resolved = $existing;
            }

            // A present-but-malformed cookie is replaced rather than trusted.
            $uuid = (string) Str::uuid();

            $this->queueCookie($request, $uuid);

            return $this->resolved = $uuid;
        } catch (\Throwable $e) {
            Log::warning('[analytics] visitor identity unavailable', ['message' => $e->getMessage()]);

            return null;
        }
    }

    protected function queueCookie(Request $request, string $uuid): void
    {
        Cookie::queue(Cookie::make(
            self::COOKIE_NAME,
            $uuid,
            self::COOKIE_DAYS * 24 * 60,
            '/',
            null,
            $request->isSecure(),
            true, // httpOnly - no storefront script needs to read this
            false,
            'lax',
        ));
    }
}
