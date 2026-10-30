<?php

return [
    /*
    |--------------------------------------------------------------------------
    | TFC Sales & Marketing Domain & Site Configuration
    |--------------------------------------------------------------------------
    |
    | Defines the production and local domain routing for TFC Sales & Marketing.
    |
    */
    'domain' => env('TFC_DOMAIN', 'tfcsalesmarketing.com'),

    'name' => 'TFC Sales & Marketing',

    'tagline' => 'Connecting Products. Creating Possibilities.',

    'logo_url' => 'https://eduse-uploadedfiles.s3.ap-south-1.amazonaws.com/media/Ti0GRLXRJGYEbWLLfsuTPg7dcEhK04S2HIxxerIH.png',

    'vanriti_store_url' => env('VANRITI_URL', 'https://vanriti.com'),

    /*
    |--------------------------------------------------------------------------
    | Direct Selling Statutory & Compliance Settings
    |--------------------------------------------------------------------------
    |
    | Per Consumer Protection (Direct Selling) Rules, 2021 & 2023 Amendment.
    | Statutory pages and mandatory officer appointments remain in 'draft'
    | state until verified and published by authorized corporate administration.
    |
    */
    'compliance' => [
        'rule_acknowledgment_hours' => 48,
        'rule_resolution_days' => 30,
        'cooling_off_days' => 30,
        'pyramid_scheme_prohibited' => true,
        'mandatory_buyback_policy' => true,
        'has_verified_nodal_officer' => false,
        'has_verified_grievance_officer' => false,
    ],
];
