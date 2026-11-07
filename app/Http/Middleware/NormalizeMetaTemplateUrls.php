<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizeMetaTemplateUrls
{
    /**
     * Handle an incoming request and normalize any malformed Meta WhatsApp template placeholders.
     * E.g. /%7B%7B1%7D%7Dtrack?order=... or /{{1}}track?order=... -> redirect 301 cleanly to /track?order=...
     */
    public function handle(Request $request, Closure $next): Response
    {
        $uri = $request->getRequestUri();

        if (str_contains($uri, '%7B%7B') || str_contains($uri, '%7b%7b') || str_contains($uri, '{{')) {
            $normalizedUri = preg_replace('/(\/)?(%7B%7B|%7b%7b|\{\{)[^%\}]+(%7D%7D|%7d%7d|\}\})/', '', $uri);

            if ($normalizedUri !== $uri) {
                if (! str_starts_with($normalizedUri, '/')) {
                    $normalizedUri = '/' . $normalizedUri;
                }

                return redirect($normalizedUri, 301);
            }
        }

        return $next($request);
    }
}
