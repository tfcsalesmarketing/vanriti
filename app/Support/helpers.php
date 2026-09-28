<?php

use App\Models\Setting;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

if (! function_exists('setting')) {
    function setting(string $key, mixed $default = null): mixed
    {
        return Setting::where('key', $key)->value('value') ?? $default;
    }
}

if (! function_exists('secret_setting')) {
    function secret_setting(string $key, mixed $default = null): mixed
    {
        $value = setting($key, $default);

        if ($value === null) {
            return $default;
        }

        if (is_string($value)) {
            try {
                return Crypt::decryptString($value);
            } catch (Throwable) {
                // Fail closed: an undecryptable secret must never be sent to a
                // gateway as if it were the plaintext value.
                Log::error('Stored secret could not be decrypted.', ['key' => $key]);

                return $default;
            }
        }

        return $value;
    }
}

if (! function_exists('store_name')) {
    function store_name(): string
    {
        return setting('store_name', config('app.name', 'VANRITI'));
    }
}

if (! function_exists('store_logo_url')) {
    function store_logo_url(): string
    {
        $logo = config('app.logo_url');

        if ($logo && (str_starts_with($logo, 'http://') || str_starts_with($logo, 'https://'))) {
            return $logo;
        }

        return asset('images/logo.webp');
    }
}

if (! function_exists('currency')) {
    function currency(): string
    {
        return setting('currency_symbol', '₹');
    }
}

if (! function_exists('csp_nonce')) {
    function csp_nonce(): string
    {
        if (! app()->bound('csp.nonce')) {
            app()->instance('csp.nonce', bin2hex(random_bytes(16)));
            \Illuminate\Support\Facades\View::share('cspNonce', app('csp.nonce'));
        }

        return (string) app('csp.nonce');
    }
}

if (! function_exists('canonical_phone')) {
    /**
     * Indian mobile numbers as a 10-digit string starting with 6-9.
     * Accepts spaces, dashes, a leading 0, and a +91 prefix.
     */
    function canonical_phone(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '91') && strlen($digits) === 12) {
            $digits = substr($digits, 2);
        }

        $digits = ltrim($digits, '0');

        if (preg_match('/^[6-9]\d{9}$/', $digits) !== 1) {
            return null;
        }

        return $digits;
    }
}

if (! function_exists('user_by_phone')) {
    /**
     * Find an account by any common spelling of the same Indian mobile number.
     */
    function user_by_phone(?string $phone): ?\App\Models\User
    {
        $canonical = canonical_phone($phone);

        if ($canonical === null) {
            return null;
        }

        return \App\Models\User::query()
            ->whereIn('phone', [$canonical, '91'.$canonical, '+91'.$canonical, '0'.$canonical])
            ->orderByRaw('CASE WHEN phone = ? THEN 0 ELSE 1 END', [$canonical])
            ->first();
    }
}

if (! function_exists('format_price')) {
    /**
     * Format an amount with the store currency and 2 decimals.
     *
     * Laravel money casts leave selling_price as `float`, but a draft / partially
     * configured product can still carry `NULL` (e.g. no variant prices set yet).
     * Rather than TypeError at every call site, we accept null and degrade to 0.00
     * so the storefront shell renders without crashing — real pricing just shows
     * as 0.00 until the merchant fills it in.
     */
    function format_price(float|int|string|null $amount): string
    {
        return currency().' '.number_format((float) ($amount ?? 0), 2);
    }
}

if (! function_exists('abbreviate_number')) {
    function abbreviate_number(int|float $value): string
    {
        if ($value >= 10000000) {
            return round($value / 10000000, 2).' Cr';
        }
        if ($value >= 100000) {
            return round($value / 100000, 2).' L';
        }
        if ($value >= 1000) {
            return round($value / 1000, 1).'K';
        }

        return (string) $value;
    }
}

if (! function_exists('generate_order_number')) {
    function generate_order_number(int $id): string
    {
        return 'VAN-'.date('Y').'-'.str_pad((string) $id, 6, '0', STR_PAD_LEFT);
    }
}

if (! function_exists('normalize_email')) {
    /**
     * Canonical form of an email address, so the same person is not stored
     * twice (and cannot dodge an unsubscribe) via casing or Gmail-style
     * dot/plus addressing.
     */
    function normalize_email(string $email): string
    {
        $email = Str::lower(trim($email));

        if (! str_contains($email, '@')) {
            return $email;
        }

        [$local, $domain] = explode('@', $email, 2);

        if (in_array($domain, ['gmail.com', 'googlemail.com'], true)) {
            $local = str_replace('.', '', $local);
            $local = preg_replace('/\+.*$/', '', $local) ?? $local;
            $domain = 'gmail.com';
        }

        return $local.'@'.$domain;
    }
}

if (! function_exists('newsletter_unsubscribe_token')) {
    /**
     * HMAC over the canonical address keyed by the app key: the resulting
     * unsubscribe link cannot be forged or edited to target another address.
     */
    function newsletter_unsubscribe_token(string $email): string
    {
        return hash_hmac('sha256', normalize_email($email), (string) config('app.key'));
    }
}

if (! function_exists('newsletter_unsubscribe_url')) {
    /**
     * Signed, unguessable unsubscribe link for a newsletter campaign.
     */
    function newsletter_unsubscribe_url(string $email): string
    {
        return route('newsletter.unsubscribe', [
            'token' => newsletter_unsubscribe_token($email),
            'email' => normalize_email($email),
        ]);
    }
}

if (! function_exists('image_url')) {
    function image_url(?string $path, ?string $default = null): string
    {
        if (blank($path)) {
            return asset($default ?? 'images/placeholder.png');
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        if (str_starts_with($path, 'storage/')) {
            return asset($path);
        }

        if (file_exists(public_path($path))) {
            return asset($path);
        }

        return Storage::disk('s3')->url($path);
    }
}

if (! function_exists('clean_html')) {
    /**
     * Sanitize admin-authored HTML for safe rendering in the storefront.
     * Removes active content (scripts, event handlers, dangerous URLs) while
     * preserving the formatting tags used by the CMS editor.
     */
    function clean_html(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return $html;
        }

        $allowedTags = [
            'p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'mark', 'small', 'span', 'div',
            'ul', 'ol', 'li', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'blockquote',
            'pre', 'code', 'hr', 'a', 'img', 'table', 'thead', 'tbody', 'tr', 'th', 'td', 'caption', 'sup', 'sub',
        ];

        $dangerousTags = [
            'script', 'style', 'iframe', 'frame', 'object', 'embed',
            'video', 'audio', 'source', 'track', 'link', 'meta', 'base',
            'form', 'input', 'textarea', 'select', 'button', 'svg', 'math',
            'template', 'noscript',
        ];

        $dom = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $sanitize = function (DOMNode $node) use (&$sanitize, $allowedTags, $dangerousTags): void {
            for ($child = $node->firstChild; $child !== null;) {
                $next = $child->nextSibling;

                if ($child instanceof DOMElement) {
                    $tag = strtolower($child->tagName);

                    if (! in_array($tag, $allowedTags, true)) {
                        if (in_array($tag, $dangerousTags, true)) {
                            $node->removeChild($child);

                            $child = $next;

                            continue;
                        }

                        // Unknown tag: sanitize its subtree first, then unwrap it
                        // keeping the (already sanitized) children. Sanitizing
                        // before promotion ensures no node escapes the allowlist.
                        $sanitize($child);

                        while ($child->firstChild) {
                            $node->insertBefore($child->firstChild, $child);
                        }
                        $node->removeChild($child);

                        $child = $next;

                        continue;
                    }

                    $allowedAttrs = $tag === 'a'
                        ? ['href', 'title']
                        : ($tag === 'img'
                            ? ['src', 'alt', 'title']
                            : ['align', 'colspan', 'rowspan']);

                    foreach (iterator_to_array($child->attributes) as $attr) {
                        $name = strtolower($attr->nodeName);

                        if (str_starts_with($name, 'on')) {
                            $child->removeAttribute($attr->nodeName);

                            continue;
                        }

                        if (! in_array($name, $allowedAttrs, true)) {
                            $child->removeAttribute($attr->nodeName);

                            continue;
                        }

                        if ($name === 'href' || $name === 'src') {
                            $value = trim((string) $attr->nodeValue);
                            $lowerValue = strtolower($value);

                            $isSafe = false;
                            if (str_starts_with($lowerValue, 'data:')) {
                                $isSafe = $name === 'src' && (bool) preg_match('#^data:image/(png|jpe?g|gif|webp|avif);base64,[a-z0-9+/=]+$#i', trim($value));
                            } else {
                                $isSafe = str_starts_with($lowerValue, 'http://')
                                    || str_starts_with($lowerValue, 'https://')
                                    || str_starts_with($lowerValue, '//')
                                    || str_starts_with($lowerValue, 'mailto:')
                                    || str_starts_with($lowerValue, 'tel:')
                                    || str_starts_with($value, '#')
                                    || str_starts_with($value, '/');
                            }

                            if (! $isSafe) {
                                $child->removeAttribute($attr->nodeName);

                                continue;
                            }
                        }
                    }

                    $sanitize($child);
                }

                $child = $next;
            }
        };

        $sanitize($dom);

        $body = $dom->getElementsByTagName('body')->item(0);

        $output = '';
        if ($body) {
            foreach (iterator_to_array($body->childNodes) as $childNode) {
                $output .= $dom->saveHTML($childNode);
            }
        } else {
            $output = $dom->saveHTML();
        }

        return trim($output);
    }
}

if (! function_exists('canonical_url')) {
    /**
     * Indexable URL for the current request: path only, plus page when paginated.
     * Tracking and filter query strings are never copied onto the canonical.
     */
    function canonical_url(?string $override = null): string
    {
        if (is_string($override) && $override !== '') {
            return $override;
        }

        $url = request()->url();
        $page = (int) request()->query('page', 0);

        if ($page > 1) {
            return $url.'?page='.$page;
        }

        return $url;
    }
}

if (! function_exists('asset_version')) {
    function asset_version(string $relativePublicPath): string
    {
        $full = public_path($relativePublicPath);
        $version = is_file($full) ? (string) filemtime($full) : '1';

        return asset($relativePublicPath).'?v='.$version;
    }
}

if (! function_exists('organization_json_ld')) {
    function organization_json_ld(?string $logoUrl = null): string
    {
        $payload = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => store_name(),
            'url' => route('home'),
            'logo' => $logoUrl ?: image_url(setting('store_logo'), 'favicon.ico'),
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => setting('store_address'),
                'addressCountry' => 'IN',
            ],
            'sameAs' => seo_social_urls(),
        ];

        return json_encode(
            $payload,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
        ) ?: '{}';
    }
}

if (! function_exists('seo_social_urls')) {
    /**
     * @return list<string>
     */
    function seo_social_urls(): array
    {
        return array_values(array_filter([
            setting('facebook_url'),
            setting('instagram_url'),
            setting('twitter_url'),
            setting('youtube_url'),
            setting('linkedin_url'),
        ], fn ($url) => is_string($url) && $url !== ''));
    }
}
