<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * @param array<int, string> $baseSources
     * @param array<int, string> $configSources
     * @return array<int, string>
     */
    private function mergeSources(array $baseSources, array $configSources = []): array
    {
        $normalized = array_map(static fn (mixed $source) => trim((string) $source), array_merge($baseSources, $configSources));
        $filtered = array_values(array_filter($normalized, static fn (string $source) => $source !== ''));

        return array_values(array_unique($filtered));
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $analyticsCsp = config('analytics.csp', []);
        $scriptSrc = $this->mergeSources([
            "'self'",
            "'unsafe-inline'",
            "'unsafe-eval'",
        ], is_array($analyticsCsp['script_src'] ?? null) ? $analyticsCsp['script_src'] : []);
        $styleSrc = $this->mergeSources([
            "'self'",
            "'unsafe-inline'",
        ]);
        $imgSrc = $this->mergeSources([
            "'self'",
            'data:',
            'https:',
        ], is_array($analyticsCsp['img_src'] ?? null) ? $analyticsCsp['img_src'] : []);
        $fontSrc = $this->mergeSources([
            "'self'",
            'data:',
        ]);
        $connectSrc = $this->mergeSources([
            "'self'",
        ], is_array($analyticsCsp['connect_src'] ?? null) ? $analyticsCsp['connect_src'] : []);
        $frameSrc = $this->mergeSources([
            "'self'",
            'https://www.googletagmanager.com',
            'https://www.youtube.com',
            'https://www.youtube-nocookie.com',
        ]);

        $csp = implode('; ', [
            "default-src 'self'",
            'script-src '.implode(' ', $scriptSrc),
            'style-src '.implode(' ', $styleSrc),
            'img-src '.implode(' ', $imgSrc),
            'font-src '.implode(' ', $fontSrc),
            'connect-src '.implode(' ', $connectSrc),
            'frame-src '.implode(' ', $frameSrc),
        ]);

        // Content Security Policy
        $response->headers->set('Content-Security-Policy', $csp);

        // HTTP Strict Transport Security
        $response->headers->set('Strict-Transport-Security',
            'max-age=31536000; includeSubDomains');

        // Prevent clickjacking
        $response->headers->set('X-Frame-Options', 'DENY');

        // Prevent MIME type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Referrer Policy
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Permissions Policy
        $response->headers->set('Permissions-Policy',
            'geolocation=(), microphone=(), camera=()');

        return $response;
    }
}
