<?php

namespace App\Support;

class MediaUrl
{
    public static function normalize(?string $url): ?string
    {
        if (!is_string($url) || trim($url) === '') {
            return $url;
        }

        $normalized = trim($url);
        if (str_starts_with($normalized, '/storage/')) {
            return $normalized;
        }

        $path = parse_url($normalized, PHP_URL_PATH);
        if (is_string($path) && str_starts_with($path, '/storage/')) {
            return $path;
        }

        if (preg_match('#(?:^|/)storage/(.+)$#', $normalized, $matches) === 1) {
            return '/storage/'.ltrim((string) $matches[1], '/');
        }

        return $normalized;
    }
}

