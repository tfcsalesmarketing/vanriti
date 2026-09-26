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
        // snippets remain allowed ('unsafe-inline'); 'unsafe-eval' is deliberately
        // omitted — nothing in the app calls eval()/new Function(). A single XSS
        // still cannot fetch to script-src-listed hosts, but cannot execute
        // string-evaluated code either.
        $response->headers->set('Content-Security-Policy', implode('; ', [
            "default-src 'self'",
            "base-uri 'self'",
            "object-src 'none'",
            "frame-ancestors 'self'",
            "form-action 'self'",
            "img-src 'self' data: blob: https:",
            "font-src 'self' data: https://fonts.gstatic.com https://cdn.jsdelivr.net",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net",
            "script-src 'self' 'unsafe-inline' https://checkout.razorpay.com https://*.razorpay.com https://www.googletagmanager.com https://connect.facebook.net https://staticxx.facebook.com https://cdn.jsdelivr.net",
            'frame-src https://checkout.razorpay.com https://*.razorpay.com https://www.googletagmanager.com https://staticxx.facebook.com',
            "connect-src 'self' https://api.razorpay.com https://checkout.razorpay.com https://www.googletagmanager.com https://connect.facebook.net https://graph.facebook.com",
        ]));

        // HSTS only on production where HTTPS is enforced.
        if (app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
