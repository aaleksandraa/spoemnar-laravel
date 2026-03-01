<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateSlugFormat
{
    /**
     * Handle an incoming request.
     *
     * Validates that slug parameters match the required format:
     * - Lowercase alphanumeric characters
     * - Hyphens and dots allowed between words
     * - Must start and end with alphanumeric character
     * - No consecutive hyphens or dots
     * - Maximum 255 characters
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get the slug parameter from the route
        $slug = $request->route('slug');

        // If slug parameter exists, validate its format
        if ($slug !== null) {
            // Validate slug format: lowercase alphanumeric with hyphens and dots
            // Pattern: ^[a-z0-9]+(?:[.-][a-z0-9]+)*$
            // - Must start with alphanumeric
            // - Can contain hyphens or dots between alphanumeric groups
            // - Must end with alphanumeric
            // - No consecutive hyphens or dots
            if (!preg_match('/^[a-z0-9]+(?:[.-][a-z0-9]+)*$/', $slug) || strlen($slug) > 255) {
                return response()->json([
                    'message' => 'Invalid slug format. Slug must contain only lowercase letters, numbers, hyphens, and dots.',
                    'errors' => [
                        'slug' => ['The slug format is invalid.']
                    ]
                ], 422);
            }
        }

        return $next($request);
    }
}
