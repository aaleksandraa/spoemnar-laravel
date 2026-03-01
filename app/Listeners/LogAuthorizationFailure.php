<?php

namespace App\Listeners;

use Illuminate\Auth\Access\Events\GateEvaluated;
use Illuminate\Support\Facades\Log;

class LogAuthorizationFailure
{
    /**
     * Handle the event.
     */
    public function handle(GateEvaluated $event): void
    {
        // Only log when authorization is denied
        if ($event->result) {
            return;
        }

        $user = $event->user;
        $ability = $event->ability;
        $arguments = $event->arguments;

        // Extract resource information
        $resourceType = null;
        $resourceId = null;

        if (!empty($arguments)) {
            $resource = $arguments[0] ?? null;
            if (is_object($resource)) {
                $resourceType = get_class($resource);
                $resourceId = $resource->id ?? null;
            } elseif (is_string($resource)) {
                $resourceType = $resource;
            }
        }

        Log::channel('security')->warning('Authorization failure', [
            'timestamp' => now()->toIso8601String(),
            'user_id' => $user?->id,
            'action' => $ability,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'ip_address' => request()->ip(),
        ]);
    }
}
