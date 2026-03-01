<?php

namespace App\Services\SEO;

use Illuminate\Support\Facades\Request;

class MetaTagService
{
    /**
     * Generate meta description for page
     *
     * @param string $pageType Page type (home, memorial, search, contact, etc.)
     * @param array $context Additional context data
     * @return string
     */
    public function generateDescription(string $pageType, array $context = []): string
    {
        $locale = app()->getLocale();
        $description = '';

        switch ($pageType) {
            case 'home':
                $description = __('seo.meta.home_description', [], $locale);
                break;

            case 'memorial':
                if (isset($context['person_name'], $context['birth_date'], $context['death_date'])) {
                    $description = __('seo.meta.memorial_description', [
                        'name' => $context['person_name'],
                        'birth_date' => $context['birth_date'],
                        'death_date' => $context['death_date'],
                    ], $locale);
                } elseif (isset($context['person_name'])) {
                    $description = __('seo.meta.memorial_description_simple', [
                        'name' => $context['person_name'],
                    ], $locale);
                } else {
                    $description = __('seo.meta.memorial_default', [], $locale);
                }
                break;

            case 'search':
                if (isset($context['search_term'])) {
                    $description = __('seo.meta.search_description', [
                        'term' => $context['search_term'],
                    ], $locale);
                } else {
                    $description = __('seo.meta.search_default', [], $locale);
                }
                break;

            case 'contact':
                $description = __('seo.meta.contact_description', [], $locale);
                break;

            default:
                $description = __('seo.meta.default_description', [], $locale);
                break;
        }

        return $this->sanitize($description, config('seo.meta.description_length.max', 160));
    }

    /**
     * Get Open Graph image URL
     *
     * @param string|null $imageUrl Optional image URL
     * @return string
     */
    public function getOgImage(?string $imageUrl = null): string
    {
        if ($imageUrl && filter_var($imageUrl, FILTER_VALIDATE_URL)) {
            return $imageUrl;
        }

        if ($imageUrl && !str_starts_with($imageUrl, 'http')) {
            return url($imageUrl);
        }

        $defaultImage = config('seo.meta.default_og_image', '/images/og-default.jpg');
        return url($defaultImage);
    }

    /**
     * Generate Twitter Card meta tags
     *
     * @param string $title Page title
     * @param string $description Meta description
     * @param string $image Image URL
     * @return array
     */
    public function getTwitterCardTags(string $title, string $description, string $image): array
    {
        $tags = [
            'twitter:card' => 'summary_large_image',
            'twitter:title' => $this->sanitize($title, 70),
            'twitter:description' => $this->sanitize($description, 200),
            'twitter:image' => $this->getOgImage($image),
        ];

        $twitterHandle = config('seo.social.twitter_handle');
        if ($twitterHandle) {
            $tags['twitter:site'] = $twitterHandle;
        }

        return $tags;
    }

    /**
     * Get canonical URL for current page
     *
     * @return string
     */
    public function getCanonicalUrl(): string
    {
        $url = Request::url();
        $locale = app()->getLocale();

        // Ensure locale is in the URL
        if (!str_contains($url, "/{$locale}/") && !str_ends_with($url, "/{$locale}")) {
            $url = preg_replace('#^(https?://[^/]+)(/|$)#', "$1/{$locale}$2", $url);
        }

        // Remove query parameters except essential ones
        $essentialParams = ['page'];
        $queryParams = Request::query();
        $filteredParams = array_intersect_key($queryParams, array_flip($essentialParams));

        if (!empty($filteredParams)) {
            $url .= '?' . http_build_query($filteredParams);
        }

        // Ensure lowercase
        return strtolower($url);
    }

    /**
     * Sanitize meta content
     *
     * @param string $content Content to sanitize
     * @param int $maxLength Maximum length
     * @return string
     */
    public function sanitize(string $content, int $maxLength = 160): string
    {
        // Remove HTML tags
        $content = strip_tags($content);

        // Remove extra whitespace
        $content = preg_replace('/\s+/', ' ', $content);
        $content = trim($content);

        // Enforce length limit
        if (mb_strlen($content) > $maxLength) {
            $content = mb_substr($content, 0, $maxLength - 3) . '...';
        }

        // Ensure minimum length for descriptions
        $minLength = config('seo.meta.description_length.min', 120);
        if ($maxLength === config('seo.meta.description_length.max', 160) && mb_strlen($content) < $minLength) {
            // If content is too short for a description, pad with site name
            $siteName = config('seo.site.name', 'Spomenar');
            $padding = " - {$siteName}";
            if (mb_strlen($content . $padding) <= $maxLength) {
                $content .= $padding;
            }
        }

        return $content;
    }
}
