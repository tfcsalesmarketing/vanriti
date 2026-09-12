<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Analytics
    |--------------------------------------------------------------------------
    |
    | Google Tag Manager container identifiers. GTM container IDs are public
    | identifiers, not secrets. Leave GTM_CONTAINER_ID empty to keep GTM
    | disabled; the storefront then loads without any GTM bootstrap.
    |
    */

    'gtm_container_id' => env('GTM_CONTAINER_ID', ''),

];