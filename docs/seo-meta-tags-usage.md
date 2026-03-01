# SEO Meta Tags Component Usage

This document explains how to use the new SEO meta tags component in your views.

## Basic Usage

The `<x-seo.meta-tags>` component automatically generates meta descriptions, Open Graph tags, Twitter Card tags, and canonical URLs.

### Default Usage (Home Page)

```blade
@push('seo-meta-tags')
    <x-seo.meta-tags page-type="home" />
@endpush
```

### Memorial Profile Page

```blade
@push('seo-meta-tags')
    <x-seo.meta-tags 
        page-type="memorial"
        :context="[
            'person_name' => $memorial->full_name,
            'birth_date' => $memorial->birth_date?->format('Y'),
            'death_date' => $memorial->death_date?->format('Y'),
        ]"
        :title="$memorial->full_name . ' - ' . config('app.name')"
        :image="$memorial->primaryPhoto?->url"
    />
@endpush
```

### Search Results Page

```blade
@push('seo-meta-tags')
    <x-seo.meta-tags 
        page-type="search"
        :context="[
            'search_term' => $query,
        ]"
    />
@endpush
```

### Contact Page

```blade
@push('seo-meta-tags')
    <x-seo.meta-tags page-type="contact" />
@endpush
```

## Component Parameters

- `page-type` (string, default: 'default'): The type of page (home, memorial, search, contact, etc.)
- `context` (array, default: []): Additional context data for generating descriptions
- `title` (string, optional): Custom page title (defaults to app name)
- `description` (string, optional): Custom meta description (auto-generated if not provided)
- `image` (string, optional): Custom Open Graph image URL (uses default if not provided)

## Generated Tags

The component automatically generates:

1. **Meta Description**: Localized, 120-160 characters, unique per page type
2. **Canonical URL**: Absolute URL with locale prefix, lowercase, essential query params only
3. **Open Graph Tags**: og:title, og:description, og:image, og:url, og:type
4. **Twitter Card Tags**: twitter:card, twitter:title, twitter:description, twitter:image, twitter:site (if configured)

## Image Lazy Loading Component

Use the `<x-lazy-image>` component for images below the fold:

```blade
<x-lazy-image 
    src="{{ $image->url }}"
    alt="{{ $image->alt_text }}"
    :width="800"
    :height="600"
    class="rounded-lg"
/>
```

### Parameters

- `src` (string, required): Image source URL
- `alt` (string, required): Alt text for accessibility
- `width` (int, optional): Image width in pixels
- `height` (int, optional): Image height in pixels
- `lazy` (bool, default: true): Whether to lazy load the image
- `class` (string, optional): CSS classes
- `srcset` (string, optional): Responsive image srcset

### Example: Hero Image (No Lazy Loading)

```blade
<x-lazy-image 
    src="{{ $heroImage }}"
    alt="Hero image"
    :width="1200"
    :height="600"
    :lazy="false"
    class="w-full"
/>
```

### Example: Gallery Image (With Lazy Loading)

```blade
<x-lazy-image 
    src="{{ $photo->url }}"
    alt="{{ $photo->caption }}"
    :width="400"
    :height="300"
    class="gallery-item"
/>
```

## Translation Files

Meta descriptions are localized using the `resources/lang/{locale}/seo.php` files. Available locales:

- `bs` - Bosnian
- `sr` - Serbian
- `hr` - Croatian
- `de` - German
- `en` - English
- `it` - Italian

## Configuration

SEO settings are configured in `config/seo.php`:

```php
'meta' => [
    'description_length' => [
        'min' => 120,
        'max' => 160,
    ],
    'default_og_image' => '/images/og-default.jpg',
],
```

## Best Practices

1. **Always provide alt text** for images
2. **Include width and height** to prevent layout shift
3. **Use lazy loading** for images below the fold
4. **Don't lazy load** hero images or above-the-fold content
5. **Provide context** for memorial pages (name, dates)
6. **Keep descriptions** between 120-160 characters
7. **Use absolute URLs** for Open Graph images
