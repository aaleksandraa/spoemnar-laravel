<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class ImageOptimizationService
{
    /**
     * Image sizes to generate for responsive images
     */
    protected array $sizes = [320, 640, 768, 1024, 1280, 1536];

    /**
     * JPEG quality setting
     */
    protected int $jpegQuality = 85;

    /**
     * WebP quality setting
     */
    protected int $webpQuality = 85;

    /**
     * Process and optimize an uploaded image
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $directory
     * @return array
     */
    public function processUploadedImage($file, string $directory = 'images'): array
    {
        $baseName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $baseName = $this->sanitizeFilename($baseName);
        $uniqueId = uniqid();
        $path = "{$directory}/{$uniqueId}";

        // Create directory if it doesn't exist
        Storage::makeDirectory("public/{$path}");

        $generatedFiles = [];

        // Generate responsive images
        foreach ($this->sizes as $size) {
            $files = $this->generateResponsiveImage($file, $path, $baseName, $size);
            $generatedFiles = array_merge($generatedFiles, $files);
        }

        // Generate original size
        $originalFiles = $this->generateResponsiveImage($file, $path, $baseName, null);
        $generatedFiles = array_merge($generatedFiles, $originalFiles);

        return [
            'path' => $path,
            'files' => $generatedFiles,
            'base_url' => Storage::url("public/{$path}"),
        ];
    }

    /**
     * Generate responsive image at specific size
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $path
     * @param string $baseName
     * @param int|null $size
     * @return array
     */
    protected function generateResponsiveImage($file, string $path, string $baseName, ?int $size): array
    {
        $image = Image::make($file);
        $suffix = $size ? "-{$size}w" : '';
        $files = [];

        // Resize if size is specified
        if ($size) {
            $image->resize($size, null, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
        }

        // Generate JPEG
        $jpegFilename = "{$baseName}{$suffix}.jpg";
        $jpegPath = storage_path("app/public/{$path}/{$jpegFilename}");
        $image->save($jpegPath, $this->jpegQuality);
        $files[] = [
            'filename' => $jpegFilename,
            'format' => 'jpeg',
            'size' => filesize($jpegPath),
        ];

        // Generate WebP
        $webpFilename = "{$baseName}{$suffix}.webp";
        $webpPath = storage_path("app/public/{$path}/{$webpFilename}");
        $image->encode('webp', $this->webpQuality)->save($webpPath);
        $files[] = [
            'filename' => $webpFilename,
            'format' => 'webp',
            'size' => filesize($webpPath),
        ];

        return $files;
    }

    /**
     * Optimize existing image
     *
     * @param string $path
     * @return bool
     */
    public function optimizeExistingImage(string $path): bool
    {
        try {
            $fullPath = Storage::path($path);

            if (!file_exists($fullPath)) {
                return false;
            }

            $image = Image::make($fullPath);
            $extension = pathinfo($path, PATHINFO_EXTENSION);

            // Optimize based on format
            if (in_array(strtolower($extension), ['jpg', 'jpeg'])) {
                $image->save($fullPath, $this->jpegQuality);
            } elseif (strtolower($extension) === 'png') {
                // PNG optimization would require additional tools
                // For now, just save with default compression
                $image->save($fullPath);
            }

            // Generate WebP version
            $webpPath = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $fullPath);
            $image->encode('webp', $this->webpQuality)->save($webpPath);

            return true;
        } catch (\Exception $e) {
            \Log::error('Image optimization failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate thumbnail
     *
     * @param string $sourcePath
     * @param int $width
     * @param int $height
     * @return string|null
     */
    public function generateThumbnail(string $sourcePath, int $width = 150, int $height = 150): ?string
    {
        try {
            $image = Image::make(Storage::path($sourcePath));

            // Crop to square and resize
            $image->fit($width, $height);

            $pathInfo = pathinfo($sourcePath);
            $thumbnailPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '_thumb.' . $pathInfo['extension'];
            $fullThumbnailPath = Storage::path($thumbnailPath);

            $image->save($fullThumbnailPath, $this->jpegQuality);

            // Generate WebP thumbnail
            $webpThumbnailPath = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $fullThumbnailPath);
            $image->encode('webp', $this->webpQuality)->save($webpThumbnailPath);

            return $thumbnailPath;
        } catch (\Exception $e) {
            \Log::error('Thumbnail generation failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get image dimensions
     *
     * @param string $path
     * @return array|null
     */
    public function getImageDimensions(string $path): ?array
    {
        try {
            $image = Image::make(Storage::path($path));
            return [
                'width' => $image->width(),
                'height' => $image->height(),
                'aspect_ratio' => $image->width() / $image->height(),
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Sanitize filename
     *
     * @param string $filename
     * @return string
     */
    protected function sanitizeFilename(string $filename): string
    {
        // Remove special characters and spaces
        $filename = preg_replace('/[^a-zA-Z0-9-_]/', '-', $filename);
        // Remove multiple dashes
        $filename = preg_replace('/-+/', '-', $filename);
        // Trim dashes from ends
        $filename = trim($filename, '-');
        // Lowercase
        $filename = strtolower($filename);

        return $filename ?: 'image';
    }

    /**
     * Delete image and all its variants
     *
     * @param string $path
     * @return bool
     */
    public function deleteImage(string $path): bool
    {
        try {
            $pathInfo = pathinfo($path);
            $directory = $pathInfo['dirname'];
            $filename = $pathInfo['filename'];

            // Delete all variants
            $patterns = [
                "{$filename}.jpg",
                "{$filename}.webp",
                "{$filename}-*.jpg",
                "{$filename}-*.webp",
                "{$filename}_thumb.jpg",
                "{$filename}_thumb.webp",
            ];

            foreach ($patterns as $pattern) {
                $files = Storage::files($directory);
                foreach ($files as $file) {
                    if (fnmatch($pattern, basename($file))) {
                        Storage::delete($file);
                    }
                }
            }

            return true;
        } catch (\Exception $e) {
            \Log::error('Image deletion failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get total size of image and its variants
     *
     * @param string $path
     * @return int
     */
    public function getImageSize(string $path): int
    {
        try {
            $pathInfo = pathinfo($path);
            $directory = $pathInfo['dirname'];
            $filename = $pathInfo['filename'];
            $totalSize = 0;

            $files = Storage::files($directory);
            foreach ($files as $file) {
                if (strpos(basename($file), $filename) === 0) {
                    $totalSize += Storage::size($file);
                }
            }

            return $totalSize;
        } catch (\Exception $e) {
            return 0;
        }
    }
}
