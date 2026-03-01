# Image Optimization Guide

This guide explains how to optimize images for the Spomenar application to improve performance and user experience.

## Overview

Image optimization is crucial for:
- Faster page load times
- Reduced bandwidth usage
- Better user experience
- Improved SEO rankings
- Lower hosting costs

## Optimization Strategies

### 1. WebP Format with Fallbacks

WebP provides superior compression compared to JPEG and PNG while maintaining quality.

**Implementation:**
```blade
<x-responsive-image 
    src="/storage/memorial-photo.jpg"
    alt="Memorial photo"
    width="800"
    height="600"
    loading="lazy"
/>
```

This component automatically:
- Serves WebP to modern browsers
- Falls back to JPEG/PNG for older browsers
- Generates multiple sizes for responsive images

### 2. Responsive Images (srcset)

Serve different image sizes based on device screen size.

**Sizes to generate:**
- 320w - Mobile portrait
- 640w - Mobile landscape / Small tablet
- 768w - Tablet portrait
- 1024w - Tablet landscape / Small desktop
- 1280w - Desktop
- 1536w - Large desktop

**Example:**
```blade
<x-responsive-image 
    src="/storage/hero-image.jpg"
    alt="Hero image"
    sizes="(max-width: 768px) 100vw, (max-width: 1024px) 50vw, 33vw"
    loading="eager"
    fetchpriority="high"
/>
```

### 3. Lazy Loading

Load images only when they're about to enter the viewport.

**Native lazy loading:**
```html
<img src="image.jpg" alt="Description" loading="lazy">
```

**Custom lazy loading with Intersection Observer:**
```blade
<x-lazy-image 
    src="/storage/gallery-photo.jpg"
    alt="Gallery photo"
    width="400"
    height="300"
/>
```

### 4. Image Dimensions

Always specify width and height to prevent layout shifts (CLS).

```html
<img 
    src="image.jpg" 
    alt="Description"
    width="800"
    height="600"
    loading="lazy"
>
```

## Image Processing Workflow

### 1. Upload Processing

When users upload images, process them server-side:

```php
use Intervention\Image\Facades\Image;

public function processUploadedImage($file)
{
    $sizes = [320, 640, 768, 1024, 1280, 1536];
    $baseName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
    $path = 'memorials/' . uniqid();
    
    foreach ($sizes as $size) {
        // Generate JPEG
        $image = Image::make($file);
        $image->resize($size, null, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });
        $image->save(storage_path("app/public/{$path}/{$baseName}-{$size}w.jpg"), 85);
        
        // Generate WebP
        $image->encode('webp', 85);
        $image->save(storage_path("app/public/{$path}/{$baseName}-{$size}w.webp"));
    }
    
    return $path;
}
```

### 2. Optimization Tools

**Server-side tools:**
- **Intervention Image** - PHP image manipulation
- **ImageMagick** - Command-line image processing
- **cwebp** - WebP conversion tool

**Build-time tools:**
- **sharp** (Node.js) - High-performance image processing
- **imagemin** - Image minification

**Installation:**
```bash
# Install Intervention Image
composer require intervention/image

# Install ImageMagick (Ubuntu/Debian)
sudo apt-get install imagemagick

# Install WebP tools
sudo apt-get install webp
```

### 3. Automated Optimization

Create a Laravel command to optimize existing images:

```php
php artisan make:command OptimizeImages
```

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;

class OptimizeImages extends Command
{
    protected $signature = 'images:optimize {--path=}';
    protected $description = 'Optimize images in storage';

    public function handle()
    {
        $path = $this->option('path') ?? 'public';
        $files = Storage::allFiles($path);
        
        $this->info("Optimizing images in {$path}...");
        $bar = $this->output->createProgressBar(count($files));
        
        foreach ($files as $file) {
            if (preg_match('/\.(jpg|jpeg|png)$/i', $file)) {
                $this->optimizeImage($file);
            }
            $bar->advance();
        }
        
        $bar->finish();
        $this->info("\nOptimization complete!");
    }
    
    private function optimizeImage($file)
    {
        $fullPath = Storage::path($file);
        $image = Image::make($fullPath);
        
        // Optimize JPEG
        if (preg_match('/\.jpe?g$/i', $file)) {
            $image->save($fullPath, 85);
        }
        
        // Generate WebP version
        $webpPath = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $fullPath);
        $image->encode('webp', 85)->save($webpPath);
    }
}
```

## Performance Best Practices

### 1. Critical Images

For above-the-fold images (hero images, logos):
- Use `loading="eager"`
- Use `fetchpriority="high"`
- Consider inlining small images as data URIs
- Preload critical images

```html
<link rel="preload" as="image" href="/images/hero.webp" type="image/webp">
<link rel="preload" as="image" href="/images/hero.jpg" type="image/jpeg">
```

### 2. Non-Critical Images

For below-the-fold images:
- Use `loading="lazy"`
- Use `decoding="async"`
- Use Intersection Observer for custom lazy loading

### 3. Image CDN

Consider using a CDN for image delivery:
- Cloudflare Images
- Cloudinary
- imgix
- AWS CloudFront

**Benefits:**
- Global edge caching
- Automatic format conversion
- On-the-fly resizing
- Reduced server load

### 4. Compression Settings

**JPEG Quality:**
- Hero images: 85-90%
- Content images: 75-85%
- Thumbnails: 70-75%

**WebP Quality:**
- Use same or slightly lower than JPEG
- WebP typically achieves better quality at lower file sizes

**PNG Optimization:**
- Use PNG only for images requiring transparency
- Consider using WebP with alpha channel instead
- Use tools like pngquant for compression

## Testing and Monitoring

### 1. Performance Testing

Use these tools to test image performance:
- **Lighthouse** - Chrome DevTools
- **WebPageTest** - Detailed performance analysis
- **GTmetrix** - Performance monitoring
- **PageSpeed Insights** - Google's performance tool

### 2. Image Analysis

Check image optimization with:
- **Squoosh** - Online image optimizer
- **ImageOptim** - Mac app for image optimization
- **TinyPNG** - Online PNG/JPEG optimizer

### 3. Metrics to Monitor

- **Largest Contentful Paint (LCP)** - Should be < 2.5s
- **Cumulative Layout Shift (CLS)** - Should be < 0.1
- **Total image size** - Aim for < 500KB per page
- **Number of images** - Minimize where possible

## Implementation Checklist

- [ ] Install image processing libraries (Intervention Image)
- [ ] Create responsive image component
- [ ] Create lazy loading component
- [ ] Implement WebP conversion
- [ ] Generate multiple image sizes
- [ ] Add width/height attributes to all images
- [ ] Implement lazy loading for below-the-fold images
- [ ] Preload critical images
- [ ] Set up image optimization command
- [ ] Configure CDN (optional)
- [ ] Test with Lighthouse
- [ ] Monitor Core Web Vitals

## Example Usage

### Hero Image (Critical)
```blade
<x-responsive-image 
    src="/storage/hero.jpg"
    alt="Welcome to Spomenar"
    width="1920"
    height="1080"
    sizes="100vw"
    loading="eager"
    fetchpriority="high"
    class="w-full h-auto"
/>
```

### Memorial Profile Image
```blade
<x-responsive-image 
    src="{{ $memorial->profile_image_url }}"
    alt="{{ $memorial->fullName }}"
    width="400"
    height="400"
    sizes="(max-width: 768px) 100vw, 400px"
    loading="lazy"
    class="rounded-full"
/>
```

### Gallery Images
```blade
@foreach($memorial->images as $image)
    <x-lazy-image 
        src="{{ $image->url }}"
        alt="{{ $image->caption }}"
        width="400"
        height="300"
        class="gallery-image"
    />
@endforeach
```

## Resources

- [WebP Documentation](https://developers.google.com/speed/webp)
- [Responsive Images Guide](https://developer.mozilla.org/en-US/docs/Learn/HTML/Multimedia_and_embedding/Responsive_images)
- [Lazy Loading Guide](https://web.dev/lazy-loading-images/)
- [Image Optimization Guide](https://web.dev/fast/#optimize-your-images)
- [Intervention Image Docs](http://image.intervention.io/)
