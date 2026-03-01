<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Failed;
use Illuminate\Support\Facades\Log;

class LogFailedLogin
{
    /**
     * Handle the event.
     */
    public function handle(Failed $event): void
    {
        $credentials = $event->credentials;
        $username = $credentials['email'] ?? $credentials['username'] ?? 'unknown';

        Log::channel('security')->warning('Failed login attempt', [
            'timestamp' => now()->toIso8601String(),
            'ip_address' => request()->ip(),
            'username' => $username,
            'user_agent' => request()->userAgent(),
        ]);
    }
}
