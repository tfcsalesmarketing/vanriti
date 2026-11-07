<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizeMetaTemplateUrls
{
    /**
     * Handle an incoming request and normalize any malformed Meta WhatsApp template placeholders
     * or double-encoded URL redirect artifacts.
     * E.g.
     * - /%7B%7B1%7D%7Dtrack?order=... -> /track?order=...
     * - /track%253Forder=VAN-2026-000025?order=VAN-2026-000025 -> /track?order=...
     * - /track%3Forder=... -> /track?order=...
     */
    public function handle(Request $request, Closure $next): Response
    {
        $uri = $request->getRequestUri();
        $original = $uri;

        // 1. Strip Meta template placeholder tokens (e.g. /%7B%7B1%7D%7D, /{{1}}, %7B%7B1%7D%7D)
        if (preg_match('/(%7B%7B|%7b%7b|\{\{)[^%\}]+(%7D%7D|%7d%7d|\}\})/i', $uri)) {
            $uri = preg_replace('/(\/)?(%7B%7B|%7b%7b|\{\{)[^%\}]+(%7D%7D|%7d%7d|\}\})/', '', $uri);
            if (! str_starts_with($uri, '/')) {
                $uri = '/' . $uri;
            }
        }

        // 2. Fix double-encoded (%253F) or single-encoded (%3F) question mark inside the path
        if (preg_match('/(%253F|%3F)/i', $uri)) {
            $parts = explode('?', $uri, 2);
            $path = $parts[0];
            $existingQuery = $parts[1] ?? '';

            if (preg_match('/(%253F|%3F)/i', $path)) {
                $pathParts = preg_split('/(%253F|%3F)/i', $path, 2);
                $realPath = $pathParts[0];
                $embeddedQuery = $pathParts[1] ?? '';

                parse_str($embeddedQuery, $q1);
                parse_str($existingQuery, $q2);
                $merged = array_merge($q1, $q2);

                $queryString = http_build_query($merged);
                $uri = $realPath . ($queryString !== '' ? '?' . $queryString : '');
            }
        }

        if (! str_starts_with($uri, '/')) {
            $uri = '/' . $uri;
        }

        if ($uri !== $original) {
            return redirect($uri, 301);
        }

        return $next($request);
    }
}
