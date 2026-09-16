<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Consent Foundation
    |--------------------------------------------------------------------------
    |
    | M5.3 storage/model layer. This phase creates the consent persistence and
    | the authoritative ConsentService resolver. It does NOT enforce consent on
    | GTM / GA4 / Meta Pixel / Meta CAPI / Dadi attribution - enforcement is a
    | later phase (M5.5). Keep these values stable; version bumps are the
    | mechanism that signals "a new choice is required" (see ConsentService).
    |
    | "necessary" is never a user choice: it is always granted. The other three
    | categories (analytics, advertising, marketing_communications) are
    | optional and default to denied (fail closed).
    |
    */

    'enabled' => (bool) env('CONSENT_ENABLED', true),

    // Single authoritative consent version. A payload/row whose version does
    // not match this value is treated as stale: optional categories resolve to
    // denied until a fresh choice is recorded for the current version.
    'version' => (string) env('CONSENT_VERSION', '2026-09-01'),

    // Privacy/cookie policy revision, recorded for provenance only. Policy
    // changes do not invalidate consent unless they are a material change in
    // scope; the re-consent trigger is the consent version above.
    'policy_version' => (string) env('CONSENT_POLICY_VERSION', '1.0'),

    /*
    | The first-party anonymous consent cookie. Value is Laravel-encrypted
    | (authenticated encryption via APP_KEY), carries no PII, HttpOnly,
    | SameSite=Lax, Path=/, and the lifetime below in days.
    */
    'cookie_name' => (string) env('CONSENT_COOKIE_NAME', 'vanriti_consent'),

    'cookie_lifetime' => (int) env('CONSENT_COOKIE_LIFETIME', 365),

    'cookie_path' => (string) env('CONSENT_COOKIE_PATH', '/'),

    'cookie_secure' => (bool) env('CONSENT_COOKIE_SECURE', false),

];
