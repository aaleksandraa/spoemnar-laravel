<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Database Backups
    |--------------------------------------------------------------------------
    |
    | Daily automated database backup configuration.
    |
    */
    'enabled' => env('DB_BACKUP_ENABLED', true),

    'connection' => env('DB_BACKUP_CONNECTION'),

    'schedule' => [
        'daily_at' => env('DB_BACKUP_DAILY_AT', '00:30'),
        'timezone' => env('DB_BACKUP_TIMEZONE', env('APP_TIMEZONE', 'UTC')),
    ],

    'retention_days' => (int) env('DB_BACKUP_RETENTION_DAYS', 30),
    'timeout_seconds' => (int) env('DB_BACKUP_TIMEOUT_SECONDS', 1200),

    'primary' => [
        'disk' => env('DB_BACKUP_DISK', 'local'),
        'path' => trim((string) env('DB_BACKUP_PATH', 'backups/database'), '/'),
    ],

    'secure_copy' => [
        'enabled' => env('DB_BACKUP_SECURE_COPY_ENABLED', false),
        'disk' => env('DB_BACKUP_SECURE_DISK', 's3'),
        'path' => trim((string) env('DB_BACKUP_SECURE_PATH', 'backups/database'), '/'),
    ],

    'tools' => [
        'pg_dump_binary' => env('PG_DUMP_BINARY', 'pg_dump'),
        'mysqldump_binary' => env('MYSQLDUMP_BINARY', 'mysqldump'),
    ],
];

