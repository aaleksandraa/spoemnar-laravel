@props([
    'src' => '',
    'alt' => '',
    'width' => null,
    'height' => null,
    'sizes' => '100vw',
    'class' => '',
    'loading' => 'lazy',
    'decoding' => 'async',
    'fetchpriority' => 'auto'
])

@php
    // Generate WebP and fallback URLs
    $pathInfo = pathinfo($src);
    $basePath = $pathInfo['dirname'] . '/' . $pathInfo['filename'];
    $extension = $pathInfo['extension'] ?? 'jpg';

    // Generate srcset for different sizes
    $sizes = [320, 640, 768, 1024, 1280, 1536];
    $srcsetWebP = [];
    $srcsetFallback = [];

    foreach ($sizes as $size) {
        $srcsetWebP[] = "{$basePath}-{$size}w.webp {$size}w";
        $srcsetFallback[] = "{$basePath}-{$size}w.{$extension} {$size}w";
    }

    $srcsetWebPString = implode(', ', $srcsetWebP);
    $srcsetFallbackString = implode(', ', $srcsetFallback);
@endphp

<picture>
    {{-- WebP format for modern browsers --}}
    <source
        type="image/webp"
        srcset="{{ $srcsetWebPString }}"
        sizes="{{ $sizes }}"
    >

    {{-- Fallback format for older browsers --}}
    <source
        type="image/{{ $extension === 'jpg' ? 'jpeg' : $extension }}"
        srcset="{{ $srcsetFallbackString }}"
        sizes="{{ $sizes }}"
    >

    {{-- Fallback img tag --}}
    <img
        src="{{ $src }}"
        alt="{{ $alt }}"
        @if($width) width="{{ $width }}" @endif
        @if($height) height="{{ $height }}" @endif
        loading="{{ $loading }}"
        decoding="{{ $decoding }}"
        fetchpriority="{{ $fetchpriority }}"
        class="{{ $class }}"
        {{ $attributes }}
    >
</picture>
