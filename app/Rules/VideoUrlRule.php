<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class VideoUrlRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            $fail('The :attribute must be a valid video URL.');
            return;
        }

        // Validate URL scheme (must be https)
        $parsedUrl = parse_url($value);
        if (!isset($parsedUrl['scheme']) || !in_array($parsedUrl['scheme'], ['http', 'https'])) {
            $fail('The :attribute must use HTTP or HTTPS protocol.');
            return;
        }

        // Check if it's a valid YouTube or Vimeo URL
        if (!$this->isValidYouTubeUrl($value) && !$this->isValidVimeoUrl($value)) {
            $fail('The :attribute must be a valid YouTube or Vimeo URL.');
            return;
        }
    }

    /**
     * Validate that the URL is a valid YouTube URL
     *
     * @param string $url
     * @return bool
     */
    private function isValidYouTubeUrl(string $url): bool
    {
        // YouTube URL patterns
        $patterns = [
            '/^https?:\/\/(www\.)?youtube\.com\/watch\?v=[\w-]+/',
            '/^https?:\/\/(www\.)?youtu\.be\/[\w-]+/',
            '/^https?:\/\/(www\.)?youtube\.com\/embed\/[\w-]+/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validate that the URL is a valid Vimeo URL
     *
     * @param string $url
     * @return bool
     */
    private function isValidVimeoUrl(string $url): bool
    {
        // Vimeo URL patterns
        $patterns = [
            '/^https?:\/\/(www\.)?vimeo\.com\/\d+/',
            '/^https?:\/\/player\.vimeo\.com\/video\/\d+/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extract video ID from YouTube or Vimeo URL
     *
     * @param string $url
     * @return string|null
     */
    public function extractVideoId(string $url): ?string
    {
        // YouTube patterns
        if (preg_match('/[?&]v=([^&]+)/', $url, $matches)) {
            return $matches[1];
        }

        if (preg_match('/youtu\.be\/([^?]+)/', $url, $matches)) {
            return $matches[1];
        }

        if (preg_match('/youtube\.com\/embed\/([^?]+)/', $url, $matches)) {
            return $matches[1];
        }

        // Vimeo patterns
        if (preg_match('/vimeo\.com\/(\d+)/', $url, $matches)) {
            return $matches[1];
        }

        if (preg_match('/player\.vimeo\.com\/video\/(\d+)/', $url, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
