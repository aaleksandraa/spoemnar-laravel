<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceRequestSizeLimit
{
    /**
     * Maximum allowed request size in bytes (10MB)
     */
    private const MAX_REQUEST_SIZE = 10485760; // 10 * 1024 * 1024

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check Content-Length header
        $contentLength = $request->header('Content-Length');

        if ($contentLength !== null && (int)$contentLength > self::MAX_REQUEST_SIZE) {
            return response()->json([
                'message' => 'Payload Too Large. Maximum request size is 10MB.'
            ], 413);
        }

        return $next($request);
    }
}
