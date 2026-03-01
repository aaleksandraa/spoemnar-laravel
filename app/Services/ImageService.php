<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageService
{
    /**
     * Upload an image file and return its URL
     *
     * @param UploadedFile $file
     * @param string $path
     * @return string Relative public storage URL for the uploaded image
     */
    public function upload(UploadedFile $file, string $path): string
    {
        // Defensive size check - ensure file doesn't exceed 5MB
        $maxSizeInBytes = 5 * 1024 * 1024; // 5MB
        if ($file->getSize() > $maxSizeInBytes) {
            throw new \InvalidArgumentException('Image file size exceeds 5MB limit');
        }

        // Validate image format
        if (!$this->validateImageFormat($file)) {
            throw new \InvalidArgumentException('Invalid image format');
        }

        // Generate unique filename
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();

        // Store file in public disk
        $storedPath = $file->storeAs($path, $filename, 'public');

        // Return a host-agnostic URL so it works on localhost/127.0.0.1/prod domains.
        return '/storage/'.ltrim($storedPath, '/');
    }

    /**
     * Delete an image file from storage
     *
     * @param string $url
     * @return bool
     */
    public function delete(string $url): bool
    {
        // Extract path from URL
        $path = $this->extractPathFromUrl($url);

        if (!$path) {
            return false;
        }

        // Delete file from storage
        return Storage::disk('public')->delete($path);
    }

    /**
     * Validate that the uploaded file is a valid image format
     *
     * @param UploadedFile $file
     * @return bool
     */
    public function validateImageFormat(UploadedFile $file): bool
    {
        $allowedMimeTypes = [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/gif',
            'image/webp',
        ];

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        $mimeType = $file->getMimeType();
        $extension = strtolower($file->getClientOriginalExtension());

        return in_array($mimeType, $allowedMimeTypes) && in_array($extension, $allowedExtensions);
    }

    /**
     * Extract storage path from URL
     *
     * @param string $url
     * @return string|null
     */
    private function extractPathFromUrl(string $url): ?string
    {
        // Remove the base storage URL to get the relative path
        $storageUrl = Storage::disk('public')->url('');

        if (str_starts_with($url, $storageUrl)) {
            return str_replace($storageUrl, '', $url);
        }

        // If URL doesn't match expected format, try to extract path after 'storage/'
        if (preg_match('/storage\/(.+)$/', $url, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
