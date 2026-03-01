<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class WhitelistedUrlRule implements ValidationRule
{
    /**
     * Whitelisted domains for profile image URLs
     * These are trusted CDNs and image hosting services
     */
    private const WHITELISTED_DOMAINS = [
        'imgur.com',
        'i.imgur.com',
        'cloudinary.com',
        'res.cloudinary.com',
        'amazonaws.com',
        's3.amazonaws.com',
        'googleusercontent.com',
        'cloudfront.net',
        'cdn.example.com',
        'images.unsplash.com',
        'unsplash.com',
        'example.com',
    ];

    /**
     * Suspicious URL schemes that should be rejected
     */
    private const BLOCKED_SCHEMES = [
        'javascript',
        'data',
        'file',
        'vbscript',
        'about',
    ];

    /**
     * Private IP ranges that should be blocked (SSRF protection)
     */
    private const PRIVATE_IP_PATTERNS = [
        '/^127\./',                    // Loopback
        '/^10\./',                     // Private network (10.0.0.0/8)
        '/^172\.(1[6-9]|2[0-9]|3[01])\./', // Private network (172.16.0.0/12)
        '/^192\.168\./',               // Private network (192.168.0.0/16)
        '/^169\.254\./',               // Link-local (169.254.0.0/16)
        '/^0\./',                      // Reserved
    ];

    /**
     * Blocked hostnames
     */
    private const BLOCKED_HOSTNAMES = [
        'localhost',
        '0.0.0.0',
    ];

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Allow null or empty values (handled by 'nullable' rule)
        if (empty($value)) {
            return;
        }

        // Parse the URL
        $parsedUrl = parse_url($value);

        if ($parsedUrl === false || !isset($parsedUrl['scheme']) || !isset($parsedUrl['host'])) {
            $fail('The :attribute must be a valid URL.');
            return;
        }

        // Check for blocked schemes (case-insensitive)
        $scheme = strtolower($parsedUrl['scheme']);
        if (in_array($scheme, self::BLOCKED_SCHEMES, true)) {
            $fail('The :attribute contains an invalid URL scheme.');
            return;
        }

        // Enforce HTTPS only
        if ($scheme !== 'https') {
            $fail('The :attribute must use HTTPS protocol.');
            return;
        }

        // Get the host
        $host = strtolower($parsedUrl['host']);

        // Check for blocked hostnames
        if (in_array($host, self::BLOCKED_HOSTNAMES, true)) {
            $fail('The :attribute contains a blocked hostname.');
            return;
        }

        // Check for .local domains (internal networks)
        if (str_ends_with($host, '.local')) {
            $fail('The :attribute contains an internal domain.');
            return;
        }

        // Check if host is an IP address
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            // Check against private IP ranges
            foreach (self::PRIVATE_IP_PATTERNS as $pattern) {
                if (preg_match($pattern, $host)) {
                    $fail('The :attribute contains a private or reserved IP address.');
                    return;
                }
            }
        }

        // Check if domain is whitelisted
        $isWhitelisted = false;
        foreach (self::WHITELISTED_DOMAINS as $allowedDomain) {
            // Check if host matches exactly or is a subdomain
            if ($host === $allowedDomain || str_ends_with($host, '.' . $allowedDomain)) {
                $isWhitelisted = true;
                break;
            }
        }

        if (!$isWhitelisted) {
            $fail('The :attribute must be from a whitelisted domain.');
            return;
        }
    }
}
