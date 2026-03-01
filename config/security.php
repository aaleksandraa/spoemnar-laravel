<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Bot Protection
    |--------------------------------------------------------------------------
    |
    | This configuration controls the bot attack protection system that
    | detects and blocks malicious bot requests targeting known exploit
    | patterns (WordPress files, PHP shells, etc.).
    |
    */

    'bot_protection' => [

        /*
        |--------------------------------------------------------------------------
        | Bot Protection Enabled
        |--------------------------------------------------------------------------
        |
        | Enable or disable the bot protection system. When disabled, all
        | requests will be processed normally without bot detection.
        |
        */

        'enabled' => env('BOT_PROTECTION_ENABLED', true),

        /*
        |--------------------------------------------------------------------------
        | Rate Limiting
        |--------------------------------------------------------------------------
        |
        | Configure rate limiting thresholds for malicious requests. When an
        | IP address exceeds these thresholds, it will be automatically blocked.
        |
        */

        'rate_limiting' => [
            'per_minute' => env('BOT_RATE_LIMIT_PER_MINUTE', 10),
            'per_hour' => env('BOT_RATE_LIMIT_PER_HOUR', 50),
            'auto_block_duration_hours' => env('BOT_AUTO_BLOCK_DURATION_HOURS', 24),
        ],

        /*
        |--------------------------------------------------------------------------
        | Logging
        |--------------------------------------------------------------------------
        |
        | Configure security event logging behavior. Events are aggregated
        | within the specified time window to reduce log noise.
        |
        */

        'logging' => [
            'aggregation_minutes' => env('BOT_LOG_AGGREGATION_MINUTES', 5),
            'channel' => 'security',
        ],

        /*
        |--------------------------------------------------------------------------
        | Exploit Patterns
        |--------------------------------------------------------------------------
        |
        | List of URL patterns that indicate malicious bot activity. Requests
        | matching these patterns will be blocked and logged to the security
        | channel.
        |
        */

        'exploit_patterns' => [
            'wp-login.php',
            'wp-content',
            'wp-admin',
            'wp-includes',
            'xmlrpc.php',
            'server.php',
            'test1.php',
            'f.php',
            'lock.php',
            'elp.php',
            'buy.php',
            'alfa.php',
            'wp-corn-sample.php',
            'adminer.php',
            'phpmyadmin',
            '.env',
            'config.php',
            'shell.php',
            'c99.php',
            'r57.php',
            'eval-stdin.php',
        ],

        /*
        |--------------------------------------------------------------------------
        | Whitelist
        |--------------------------------------------------------------------------
        |
        | Trusted IP addresses that bypass all bot detection and rate limiting.
        | Useful for monitoring services, load balancers, or trusted partners.
        |
        */

        'whitelist' => [
            // '127.0.0.1',
            // '::1',
        ],

    ],

];
