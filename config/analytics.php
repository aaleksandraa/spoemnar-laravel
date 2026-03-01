<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Google Tag Manager Configuration
    |--------------------------------------------------------------------------
    |
    | GTM container configuration with environment-based container ID selection.
    | GTM is disabled in development environment and uses staging/production
    | container IDs based on APP_ENV.
    |
    */
    'gtm' => [
        'enabled' => env('ANALYTICS_ENABLED', false),
        'container_id' => env('APP_ENV') === 'production'
            ? env('GTM_ID')
            : env('GTM_ID_STAGING'),
        'debug_mode' => env('ANALYTICS_DEBUG_MODE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Google Analytics 4 Configuration
    |--------------------------------------------------------------------------
    |
    | GA4 measurement configuration. The measurement ID is configured via GTM
    | but stored here for reference and potential direct integration.
    |
    */
    'ga4' => [
        'measurement_id' => env('GA4_MEASUREMENT_ID'),
        'debug_mode' => env('ANALYTICS_DEBUG_MODE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cookie Consent Configuration
    |--------------------------------------------------------------------------
    |
    | GDPR-compliant cookie consent settings including version tracking,
    | expiration period, and localStorage key.
    |
    */
    'consent' => [
        'version' => 1,
        'expiration_months' => 12,
        'storage_key' => 'cookie_consent',
    ],

    /*
    |--------------------------------------------------------------------------
    | Content Security Policy Directives
    |--------------------------------------------------------------------------
    |
    | CSP directives for GTM and GA4 domains. These should be added to your
    | CSP middleware configuration to allow analytics scripts and connections.
    |
    */
    'csp' => [
        'script_src' => [
            'https://www.googletagmanager.com',
            'https://www.google-analytics.com',
        ],
        'connect_src' => [
            'https://www.google-analytics.com',
            'https://analytics.google.com',
            'https://stats.g.doubleclick.net',
        ],
        'img_src' => [
            'https://www.google-analytics.com',
            'https://www.googletagmanager.com',
        ],
    ],
];
