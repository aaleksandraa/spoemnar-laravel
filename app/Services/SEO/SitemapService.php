<?php

namespace App\Services\SEO;

use App\Models\Memorial;
use App\Support\LocaleResolver;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;

class SitemapService
{
    /**
     * Generate sitemap for specific locale
     *
     * @param string $locale
     * @return string XML content
     */
    public function generateSitemap(string $locale): string
    {
        $urls = [];

        // Add homepage
        $urls[] = [
            'loc' => route('home', ['locale' => $locale]),
            'lastmod' => $this->getLastModForStaticPage('home'),
            'changefreq' => $this->getChangeFreq('home'),
            'priority' => $this->getPriority('home'),
        ];

        // Add static pages
        $staticPages = ['about', 'contact', 'search.page', 'privacy', 'terms'];
        foreach ($staticPages as $page) {
            $urls[] = [
                'loc' => route($page, ['locale' => $locale]),
                'lastmod' => $this->getLastModForStaticPage($page),
                'changefreq' => $this->getChangeFreq('static'),
                'priority' => $this->getPriority('static'),
            ];
        }

        // Add memorial profiles
        $memorials = Memorial::where('is_public', true)
            ->select(['slug', 'updated_at'])
            ->orderByDesc('updated_at')
            ->get();

        foreach ($memorials as $memorial) {
            $urls[] = [
                'loc' => route('memorial.profile', ['locale' => $locale, 'slug' => $memorial->slug]),
                'lastmod' => $memorial->updated_at?->toAtomString() ?? now()->toAtomString(),
                'changefreq' => $this->getChangeFreq('memorial'),
                'priority' => $this->getPriority('memorial'),
            ];
        }

        return $this->generateXml($urls);
    }

    /**
     * Generate sitemap index
     *
     * @return string XML content
     */
    public function generateSitemapIndex(): string
    {
        $locales = LocaleResolver::supportedLocales();
        $sitemaps = [];

        foreach ($locales as $locale) {
            $sitemaps[] = [
                'loc' => route('sitemap.locale', ['locale' => $locale]),
                'lastmod' => now()->toAtomString(),
            ];
        }

        return $this->generateSitemapIndexXml($sitemaps);
    }

    /**
     * Get priority for page type
     *
     * @param string $pageType
     * @return float
     */
    public function getPriority(string $pageType): float
    {
        $priorities = Config::get('seo.sitemap.priorities', [
            'home' => 1.0,
            'memorial' => 0.8,
            'static' => 0.6,
            'search' => 0.5,
        ]);

        return $priorities[$pageType] ?? 0.5;
    }

    /**
     * Get change frequency for page type
     *
     * @param string $pageType
     * @return string
     */
    public function getChangeFreq(string $pageType): string
    {
        $frequencies = Config::get('seo.sitemap.change_frequencies', [
            'home' => 'daily',
            'memorial' => 'weekly',
            'static' => 'monthly',
        ]);

        return $frequencies[$pageType] ?? 'monthly';
    }

    /**
     * Get last modified timestamp for static page
     *
     * @param string $pageName
     * @return string
     */
    private function getLastModForStaticPage(string $pageName): string
    {
        $fallbackLastmod = now()->toAtomString();

        // Files that affect all pages
        $sharedTemplateFiles = [
            resource_path('views/layouts/app.blade.php'),
            resource_path('views/components/header.blade.php'),
            resource_path('views/components/footer.blade.php'),
            base_path('routes/web.php'),
        ];

        // Page-specific template files
        $routeSpecificTemplateFiles = [
            'home' => [resource_path('views/home.blade.php')],
            'about' => [resource_path('views/about.blade.php')],
            'contact' => [resource_path('views/contact.blade.php')],
            'search.page' => [resource_path('views/search.blade.php')],
            'privacy' => [resource_path('views/privacy.blade.php')],
            'terms' => [resource_path('views/terms.blade.php')],
        ];

        // Translation files
        $translationFiles = glob(resource_path('lang/*/ui.php')) ?: [];

        $files = array_merge(
            $sharedTemplateFiles,
            $routeSpecificTemplateFiles[$pageName] ?? [],
            $translationFiles
        );

        return $this->resolveLastmodFromFiles($files, $fallbackLastmod);
    }

    /**
     * Resolve last modified timestamp from files
     *
     * @param array $files
     * @param string $fallback
     * @return string
     */
    private function resolveLastmodFromFiles(array $files, string $fallback): string
    {
        $latestTimestamp = 0;

        foreach ($files as $file) {
            if (!is_string($file) || $file === '' || !is_file($file)) {
                continue;
            }

            $fileTimestamp = filemtime($file);
            if (is_int($fileTimestamp) && $fileTimestamp > $latestTimestamp) {
                $latestTimestamp = $fileTimestamp;
            }
        }

        if ($latestTimestamp <= 0) {
            return $fallback;
        }

        return gmdate(DATE_ATOM, $latestTimestamp);
    }

    /**
     * Generate XML for sitemap
     *
     * @param array $urls
     * @return string
     */
    private function generateXml(array $urls): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($urls as $url) {
            $xml .= "  <url>\n";
            $xml .= "    <loc>" . htmlspecialchars($url['loc'], ENT_XML1, 'UTF-8') . "</loc>\n";
            $xml .= "    <lastmod>" . htmlspecialchars($url['lastmod'], ENT_XML1, 'UTF-8') . "</lastmod>\n";
            $xml .= "    <changefreq>" . htmlspecialchars($url['changefreq'], ENT_XML1, 'UTF-8') . "</changefreq>\n";
            $xml .= "    <priority>" . number_format($url['priority'], 1) . "</priority>\n";
            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';

        return $xml;
    }

    /**
     * Generate XML for sitemap index
     *
     * @param array $sitemaps
     * @return string
     */
    private function generateSitemapIndexXml(array $sitemaps): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($sitemaps as $sitemap) {
            $xml .= "  <sitemap>\n";
            $xml .= "    <loc>" . htmlspecialchars($sitemap['loc'], ENT_XML1, 'UTF-8') . "</loc>\n";
            $xml .= "    <lastmod>" . htmlspecialchars($sitemap['lastmod'], ENT_XML1, 'UTF-8') . "</lastmod>\n";
            $xml .= "  </sitemap>\n";
        }

        $xml .= '</sitemapindex>';

        return $xml;
    }
}
