<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Meta Conversions API (CAPI)
    |--------------------------------------------------------------------------
    |
    | Server-side delivery of the Meta Purchase conversion. The access token
    | itself is never stored in env/config: it lives in the encrypted
    | `meta_capi_access_token` Settings row and is read via secret_setting().
    |
    | META_CAPI_ENABLED is the global operator switch. The Graph API version
    | below (v26.0, released July 29 2026) was validated as the current
    | supported Graph API version at build time. Re-verify before each deploy.
    |
    */

    'enabled' => (bool) env('META_CAPI_ENABLED', false),

    'api_version' => (string) env('META_CAPI_API_VERSION', '26.0'),

    'timeout' => (int) env('META_CAPI_TIMEOUT', 5),

    'connect_timeout' => (int) env('META_CAPI_CONNECT_TIMEOUT', 3),

    'max_attempts' => 3,

    /*
    | The delivery category attached to each contents entry, per Meta's v26
    | contents schema. e-commerce orders are home deliveries.
    */
    'delivery_category' => 'home_delivery',

];