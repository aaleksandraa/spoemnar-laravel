<?php

namespace App\Services\SEO;

use App\Models\Memorial;
use Illuminate\Support\Facades\Config;

class StructuredDataService
{
    /**
     * Generate Organization schema
     *
     * @return array
     */
    public function generateOrganizationSchema(): array
    {
        $config = Config::get('seo.structured_data.organization', []);
        $social = Config::get('seo.social', []);
        $siteUrl = Config::get('seo.site.url', url('/'));

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $config['name'] ?? Config::get('seo.site.name', 'Spomenar'),
            'url' => $siteUrl,
        ];

        // Add logo if configured
        if (!empty($config['logo'])) {
            $schema['logo'] = $siteUrl . $config['logo'];
        }

        // Add social media profiles
        $sameAs = [];
        if (!empty($social['facebook'])) {
            $sameAs[] = $social['facebook'];
        }
        if (!empty($social['twitter'])) {
            $sameAs[] = $social['twitter'];
        }
        if (!empty($sameAs)) {
            $schema['sameAs'] = $sameAs;
        }

        // Add contact point
        if (!empty($config['contact_email'])) {
            $schema['contactPoint'] = [
                '@type' => 'ContactPoint',
                'contactType' => 'customer service',
                'email' => $config['contact_email'],
            ];
        }

        return $schema;
    }

    /**
     * Generate WebSite schema with SearchAction
     *
     * @return array
     */
    public function generateWebSiteSchema(): array
    {
        $siteName = Config::get('seo.site.name', 'Spomenar');
        $siteUrl = Config::get('seo.site.url', url('/'));

        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => $siteName,
            'url' => $siteUrl,
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => $siteUrl . '/search?q={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    /**
     * Generate Person schema for memorial profile
     *
     * @param Memorial $memorial
     * @return array
     */
    public function generatePersonSchema(Memorial $memorial): array
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Person',
            'name' => $memorial->first_name . ' ' . $memorial->last_name,
        ];

        // Add birth date if available
        if ($memorial->birth_date) {
            $schema['birthDate'] = $memorial->birth_date->format('Y-m-d');
        }

        // Add death date if available
        if ($memorial->death_date) {
            $schema['deathDate'] = $memorial->death_date->format('Y-m-d');
        }

        // Add image URL if available
        if ($memorial->primaryPhoto) {
            $schema['image'] = $memorial->primaryPhoto->url;
        }

        // Add description if available
        if (!empty($memorial->biography)) {
            // Sanitize and limit description length
            $description = strip_tags($memorial->biography);
            $description = mb_substr($description, 0, 500);
            $schema['description'] = $description;
        }

        return $schema;
    }

    /**
     * Generate BreadcrumbList schema
     *
     * @param array $breadcrumbs Array of breadcrumb items with 'name' and 'url' keys
     * @return array
     */
    public function generateBreadcrumbSchema(array $breadcrumbs): array
    {
        $itemListElement = [];
        $position = 1;

        foreach ($breadcrumbs as $breadcrumb) {
            $itemListElement[] = [
                '@type' => 'ListItem',
                'position' => $position,
                'name' => $breadcrumb['name'],
                'item' => $breadcrumb['url'],
            ];
            $position++;
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $itemListElement,
        ];
    }

    /**
     * Convert schema array to JSON-LD string
     *
     * @param array $schema
     * @return string
     */
    public function toJsonLd(array $schema): string
    {
        return json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * Validate schema against Schema.org
     * Basic validation - checks for required fields
     *
     * @param array $schema
     * @return bool
     */
    public function validateSchema(array $schema): bool
    {
        // Check for required @context and @type
        if (empty($schema['@context']) || empty($schema['@type'])) {
            return false;
        }

        // Validate based on schema type
        switch ($schema['@type']) {
            case 'Organization':
                return !empty($schema['name']) && !empty($schema['url']);

            case 'WebSite':
                return !empty($schema['name']) && !empty($schema['url']);

            case 'Person':
                return !empty($schema['name']);

            case 'BreadcrumbList':
                return !empty($schema['itemListElement']) && is_array($schema['itemListElement']);

            default:
                return true;
        }
    }
}
