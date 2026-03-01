<?php

namespace App\Http\Middleware;

use App\Services\Security\ExploitPatternMatcher;
use App\Services\Security\RateLimiter;
use App\Services\Security\SecurityLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BotAttackDetector
{
    public function __construct(
        private ExploitPatternMatcher $patternMatcher,
        private RateLimiter $rateLimiter,
        private SecurityLogger $securityLogger
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if bot protection is enabled
        if (!config('security.bot_protection.enabled', true)) {
            return $next($request);
        }

        $ip = $request->ip();

        // Check if this is a malicious request
        if ($this->isMaliciousRequest($request)) {
            // Record the malicious request for rate limiting
            $this->rateLimiter->recordMaliciousRequest($ip);

            // Get the matched pattern for logging
            $pattern = $this->patternMatcher->getMatchedPattern($request->path());
            if (!$pattern && $this->isNonExistentPhpFile($request->path())) {
                $pattern = 'non_existent_php_file';
            } elseif (!$pattern && $this->hasPathTraversal($request->path())) {
                $pattern = 'path_traversal';
            }

            // Log the malicious request
            $this->securityLogger->logMaliciousRequest($request, $pattern ?? 'unknown');

            // Check if IP is now rate limited
            if ($this->rateLimiter->isRateLimited($ip)) {
                return response()->json([
                    'message' => 'Too many requests. Please try again later.',
                    'retry_after' => 900
                ], 429);
            }

            // Return 403 Forbidden for malicious requests
            return response()->json([
                'message' => 'Forbidden'
            ], 403);
        }

        return $next($request);
    }

    /**
     * Check if the request is malicious
     */
    private function isMaliciousRequest(Request $request): bool
    {
        $url = $request->path();

        return $this->matchesExploitPattern($url)
            || $this->isNonExistentPhpFile($url)
            || $this->hasPathTraversal($url);
    }

    /**
     * Check if URL matches known exploit patterns
     */
    private function matchesExploitPattern(string $url): bool
    {
        return $this->patternMatcher->matches($url);
    }

    /**
     * Check if URL is a non-existent PHP file
     */
    private function isNonExistentPhpFile(string $url): bool
    {
        // Check if URL ends with .php
        if (!str_ends_with(strtolower($url), '.php')) {
            return false;
        }

        // Check if the file exists in the public directory
        $publicPath = public_path($url);

        // If file doesn't exist, it's a malicious request
        return !file_exists($publicPath);
    }

    /**
     * Check if URL contains path traversal patterns
     */
    private function hasPathTraversal(string $url): bool
    {
        // Check for ../ or ..\ patterns
        return str_contains($url, '../') || str_contains($url, '..\\');
    }
}
