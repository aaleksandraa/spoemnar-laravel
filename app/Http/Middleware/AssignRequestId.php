<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * AssignRequestId Middleware
 *
 * Generates and tracks unique request IDs for debugging and auditing.
 *
 * This middleware:
 * 1. Generates a unique request ID using UUID
 * 2. Adds the ID to request attributes for access in controllers/services
 * 3. Adds the ID to response headers as X-Request-ID
 * 4. Adds the ID to log context for all subsequent log entries
 *
 * **Validates: Requirements 2.31**
 */
class AssignRequestId
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Generate unique request ID using UUID
        $requestId = (string) Str::uuid();

        // Add to request attributes for access in controllers/services
        $request->attributes->set('request_id', $requestId);

        // Add to log context for all subsequent log entries
        Log::withContext([
            'request_id' => $requestId,
        ]);

        // Process the request
        $response = $next($request);

        // Add to response headers as X-Request-ID
        $response->headers->set('X-Request-ID', $requestId);

        return $response;
    }
}
