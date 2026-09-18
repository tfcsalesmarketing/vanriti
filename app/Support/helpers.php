<?php

use App\Models\Setting;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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
                if (str_starts_with($value, 'eyJ')) {
                    Log::warning('Stored secret could not be decrypted.', ['key' => $key]);
                }

                return $value;
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

if (! function_exists('format_price')) {
    function format_price(float|int|string $amount): string
    {
        return currency().' '.number_format((float) $amount, 2);
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

                        // Unknown tag: unwrap it, keeping its (sanitized) children.
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
