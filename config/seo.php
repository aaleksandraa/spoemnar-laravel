<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Site Configuration
    |--------------------------------------------------------------------------
    |
    | Basic site information used across SEO components.
    |
    */
    'site' => [
        'name' => env('SITE_NAME', 'Spomenar'),
        'url' => env('SITE_URL', 'https://example.com'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Google Search Console
    |--------------------------------------------------------------------------
    |
    | Verification code for Google Search Console.
    |
    */
    'search_console' => [
        'verification' => env('GOOGLE_SEARCH_CONSOLE_VERIFICATION'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Social Media
    |--------------------------------------------------------------------------
    |
    | Social media profile URLs and handles for structured data and meta tags.
    |
    */
    'social' => [
        'facebook' => env('FACEBOOK_URL'),
        'twitter' => env('TWITTER_URL'),
        'twitter_handle' => env('TWITTER_HANDLE'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Structured Data
    |--------------------------------------------------------------------------
    |
    | Configuration for Schema.org structured data generation.
    |
    */
    'structured_data' => [
        'organization' => [
            'name' => env('SITE_NAME', 'Spomenar'),
            'logo' => '/logo.png',
            'contact_email' => 'info@example.com',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Meta Tags
    |--------------------------------------------------------------------------
    |
    | Configuration for meta tag generation including length constraints
    | and default images.
    |
    */
    'meta' => [
        'description_length' => [
            'min' => 120,
            'max' => 160,
        ],
        'default_og_image' => '/images/og-default.jpg',
    ],

    /*
    |--------------------------------------------------------------------------
    | Sitemap Configuration
    |--------------------------------------------------------------------------
    |
    | Priority and change frequency values for different page types.
    |
    */
    'sitemap' => [
        'priorities' => [
            'home' => 1.0,
            'memorial' => 0.8,
            'static' => 0.6,
            'search' => 0.5,
        ],
        'change_frequencies' => [
            'home' => 'daily',
            'memorial' => 'weekly',
            'static' => 'monthly',
        ],
    ],
];
