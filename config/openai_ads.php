<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OpenAI ChatGPT Ads Measurement & Conversions API
    |--------------------------------------------------------------------------
    |
    | Configuration for OpenAI Measurement Pixel and Conversions API (CAPI).
    | Secrets are retrieved from encrypted settings or environment variables,
    | never hardcoded.
    |
    */

    'enabled' => (bool) env('OPENAI_ADS_ENABLED', true),

    'pixel_id' => env('OPENAI_PIXEL_ID', 'BBWekc1tuk8CcLkcNrLBzk'),

    'source_id' => env('OPENAI_SOURCE_ID', 'cds_6ae63e02f93c819795162116d99676e4'),

    'event_setting_name' => env('OPENAI_EVENT_SETTING_NAME', 'VANRITI Purchases'),

    'event_type' => 'order_created',

    'capi_key' => env('OPENAI_CAPI_KEY', ''),

    'endpoint' => env('OPENAI_CAPI_ENDPOINT', 'https://bzr.openai.com/v1/events'),

    'validate_only' => (bool) env('OPENAI_CAPI_VALIDATE_ONLY', true),

    'timeout' => (int) env('OPENAI_CAPI_TIMEOUT', 5),

    'connect_timeout' => (int) env('OPENAI_CAPI_CONNECT_TIMEOUT', 3),

    'max_attempts' => 3,

    // OpenAI Advertiser Reporting API
    'advertiser_api_key' => env('OPENAI_ADVERTISER_API_KEY', ''),

    'advertiser_account_id' => env('OPENAI_ADVERTISER_ACCOUNT_ID', ''),

    'advertiser_api_endpoint' => env('OPENAI_ADVERTISER_API_ENDPOINT', 'https://api.ads.openai.com/v1'),

];

