<?php

namespace App\Services;

class VideoService
{
    /**
     * Validate that the URL is a valid YouTube URL
     *
     * @param string $url
     * @return bool
     */
    public function validateYouTubeUrl(string $url): bool
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
     * Extract video ID from YouTube URL
     *
     * @param string $url
     * @return string|null
     */
    public function extractVideoId(string $url): ?string
    {
        // Pattern for youtube.com/watch?v=VIDEO_ID
        if (preg_match('/[?&]v=([^&]+)/', $url, $matches)) {
            return $matches[1];
        }

        // Pattern for youtu.be/VIDEO_ID
        if (preg_match('/youtu\.be\/([^?]+)/', $url, $matches)) {
            return $matches[1];
        }

        // Pattern for youtube.com/embed/VIDEO_ID
        if (preg_match('/youtube\.com\/embed\/([^?]+)/', $url, $matches)) {
            return $matches[1];
        }

        return null;
    }
}

