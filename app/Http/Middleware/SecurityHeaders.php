<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), interest-cohort=()');

        // Content-Security-Policy: restricts where scripts/styles/frames can load
        // (Razorpay, GTM, Meta pixel, Google Fonts, remixicon CDN). Inline
        // snippets carry a per-request nonce. 'unsafe-eval' stays omitted.
        // Styles still allow inline rules because the templates use them.
        $response->headers->set('Content-Security-Policy', implode('; ', [
            "default-src 'self'",
            "base-uri 'self'",
            "object-src 'none'",
            "frame-ancestors 'self'",
            "form-action 'self'",
            "img-src 'self' data: blob: https:",
            "media-src 'self' data: blob: https:",
            "font-src 'self' data: https://fonts.gstatic.com https://cdn.jsdelivr.net",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net",
            "script-src 'self' 'nonce-".csp_nonce()."' https://checkout.razorpay.com https://*.razorpay.com https://www.googletagmanager.com https://connect.facebook.net https://staticxx.facebook.com https://cdn.jsdelivr.net https://www.youtube.com https://www.youtube-nocookie.com https://player.vimeo.com https://www.clarity.ms https://apis.google.com https://www.gstatic.com",
            'frame-src https://checkout.razorpay.com https://*.razorpay.com https://www.googletagmanager.com https://staticxx.facebook.com https://www.youtube.com https://www.youtube-nocookie.com https://player.vimeo.com https://www.google.com https://*.google.com',
            "connect-src 'self' https://api.razorpay.com https://checkout.razorpay.com https://www.googletagmanager.com https://connect.facebook.net https://graph.facebook.com https://*.google-analytics.com https://api.postalpincode.in https://www.clarity.ms https://*.clarity.ms https://apis.google.com https://www.google.com https://*.google.com https://www.gstatic.com",
        ]));

        // HSTS only on production where HTTPS is enforced.
        if (app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
