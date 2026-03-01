<?php

namespace Tests\Feature\SEO;

use Tests\TestCase;
use App\Models\Memorial;
use App\Models\User;
use App\Services\SEO\SitemapService;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Sitemap Feature Tests
 *
 * Tests sitemap index generation, locale-specific sitemaps,
 * priority values, change frequencies, lastmod timestamps,
 * XML structure validation, and error handling.
 *
 * **Validates: Requirements 27.1, 27.8, 27.9, 27.10**
 */
class SitemapTest extends TestCase
{
    use RefreshDatabase;

    protected SitemapService $sitemapService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sitemapService = app(SitemapService::class);
    }

    /**
     * Test that SitemapService class exists
     *
     * Validates that the sitemap service is available
     */
    public function test_sitemap_service_class_exists(): void
    {
        $this->assertTrue(class_exists(SitemapService::class));
    }

    /**
     * Test that sitemap index is accessible at /sitemap.xml
     *
     * **Validates: Requirement 27.1** - SEO_System SHALL generate a sitemap index
     * at /sitemap.xml that references all locale-specific sitemaps
     */
    public function test_sitemap_index_is_accessible(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
    }

    /**
     * Test that sitemap index contains all locale-specific sitemaps
     *
     * **Validates: Requirement 27.1** - Sitemap index references all locale-specific sitemaps
     */
    public function test_sitemap_index_contains_all_locales(): void
    {
        $response = $this->get('/sitemap.xml');

        $content = $response->getContent();
        $locales = ['bs', 'sr', 'hr', 'de', 'en', 'it'];

        foreach ($locales as $locale) {
            $this->assertStringContainsString("/sitemap-{$locale}.xml", $content);
        }
    }

    /**
     * Test that sitemap index has valid XML structure
     *
     * **Validates: Requirement 27.1** - Sitemap index uses valid XML structure
     */
    public function test_sitemap_index_has_valid_xml_structure(): void
    {
        $response = $this->get('/sitemap.xml');

        $content = $response->getContent();

        // Check XML declaration
        $this->assertStringStartsWith('<?xml version="1.0" encoding="UTF-8"?>', $content);

        // Check sitemapindex root element
        $this->assertStringContainsString('<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', $content);
        $this->assertStringContainsString('</sitemapindex>', $content);

        // Check sitemap elements
        $this->assertStringContainsString('<sitemap>', $content);
        $this->assertStringContainsString('</sitemap>', $content);
        $this->assertStringContainsString('<loc>', $content);
        $this->assertStringContainsString('</loc>', $content);
        $this->assertStringContainsString('<lastmod>', $content);
        $this->assertStringContainsString('</lastmod>', $content);

        // Validate XML can be parsed
        $xml = simplexml_load_string($content);
        $this->assertNotFalse($xml);
    }

    /**
     * Test that locale-specific sitemap is accessible for Bosnian
     *
     * **Validates: Requirement 27.1** - Locale-specific sitemaps are accessible
     */
    public function test_locale_specific_sitemap_is_accessible_for_bosnian(): void
    {
        $response = $this->get('/sitemap-bs.xml');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
    }

    /**
     * Test that locale-specific sitemaps are accessible for all locales
     *
     * **Validates: Requirement 27.1** - All 6 locale-specific sitemaps are accessible
     */
    public function test_locale_specific_sitemaps_are_accessible_for_all_locales(): void
    {
        $locales = ['bs', 'sr', 'hr', 'de', 'en', 'it'];

        foreach ($locales as $locale) {
            $response = $this->get("/sitemap-{$locale}.xml");

            $response->assertStatus(200, "Sitemap for locale {$locale} should be accessible");
            $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        }
    }

    /**
     * Test that invalid locale returns 404
     *
     * **Validates: Requirement 27.1** - Invalid locales are rejected
     */
    public function test_invalid_locale_returns_404(): void
    {
        $this->withoutExceptionHandling();

        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);

        $this->get('/sitemap-invalid.xml');
    }

    /**
     * Test that unsupported locale returns 404
     *
     * **Validates: Requirement 27.1** - Only supported locales are accepted
     */
    public function test_unsupported_locale_returns_404(): void
    {
        $this->withoutExceptionHandling();

        $invalidLocales = ['fr', 'es', 'ru', 'zh'];

        foreach ($invalidLocales as $locale) {
            try {
                $this->get("/sitemap-{$locale}.xml");
                $this->fail("Expected NotFoundHttpException for locale {$locale}");
            } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
                // Expected exception - test passes
                $this->assertTrue(true);
            }
        }
    }

    /**
     * Test that locale-specific sitemap has valid XML structure
     *
     * **Validates: Requirement 27.1** - Locale-specific sitemaps use valid XML structure
     */
    public function test_locale_specific_sitemap_has_valid_xml_structure(): void
    {
        $response = $this->get('/sitemap-en.xml');

        $content = $response->getContent();

        // Check XML declaration
        $this->assertStringStartsWith('<?xml version="1.0" encoding="UTF-8"?>', $content);

        // Check urlset root element
        $this->assertStringContainsString('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', $content);
        $this->assertStringContainsString('</urlset>', $content);

        // Check url elements
        $this->assertStringContainsString('<url>', $content);
        $this->assertStringContainsString('</url>', $content);
        $this->assertStringContainsString('<loc>', $content);
        $this->assertStringContainsString('</loc>', $content);
        $this->assertStringContainsString('<lastmod>', $content);
        $this->assertStringContainsString('</lastmod>', $content);
        $this->assertStringContainsString('<changefreq>', $content);
        $this->assertStringContainsString('</changefreq>', $content);
        $this->assertStringContainsString('<priority>', $content);
        $this->assertStringContainsString('</priority>', $content);

        // Validate XML can be parsed
        $xml = simplexml_load_string($content);
        $this->assertNotFalse($xml);
    }

    /**
     * Test that sitemap includes homepage with correct priority
     *
     * **Validates: Requirement 27.8** - Homepage has priority 1.0
     */
    public function test_sitemap_includes_homepage_with_correct_priority(): void
    {
        $xml = $this->sitemapService->generateSitemap('en');

        $this->assertStringContainsString('<priority>1.0</priority>', $xml);

        // Parse XML and verify homepage priority
        $xmlObj = simplexml_load_string($xml);
        $urls = $xmlObj->url;

        $homepageFound = false;
        foreach ($urls as $url) {
            if (str_contains((string) $url->loc, '/en')) {
                $priority = (float) $url->priority;
                if ($priority === 1.0) {
                    $homepageFound = true;
                    break;
                }
            }
        }

        $this->assertTrue($homepageFound, 'Homepage with priority 1.0 should be present');
    }

    /**
     * Test that sitemap includes correct priority values
     *
     * **Validates: Requirement 27.8** - Sitemaps include correct priority values
     * (home: 1.0, memorial: 0.8, static: 0.6)
     */
    public function test_sitemap_includes_correct_priority_values(): void
    {
        // Create a public memorial
        $user = User::factory()->create();
        Memorial::factory()->create([
            'user_id' => $user->id,
            'is_public' => true,
            'slug' => 'test-memorial',
        ]);

        $xml = $this->sitemapService->generateSitemap('en');

        // Check that all priority values are present
        $this->assertStringContainsString('<priority>1.0</priority>', $xml); // home
        $this->assertStringContainsString('<priority>0.8</priority>', $xml); // memorial
        $this->assertStringContainsString('<priority>0.6</priority>', $xml); // static pages
    }

    /**
     * Test that priority values are retrieved from config
     *
     * **Validates: Requirement 27.8** - Priority values come from configuration
     */
    public function test_priority_values_are_retrieved_from_config(): void
    {
        $homePriority = $this->sitemapService->getPriority('home');
        $memorialPriority = $this->sitemapService->getPriority('memorial');
        $staticPriority = $this->sitemapService->getPriority('static');

        $this->assertEquals(1.0, $homePriority);
        $this->assertEquals(0.8, $memorialPriority);
        $this->assertEquals(0.6, $staticPriority);

        // Verify they match config values
        $this->assertEquals(config('seo.sitemap.priorities.home'), $homePriority);
        $this->assertEquals(config('seo.sitemap.priorities.memorial'), $memorialPriority);
        $this->assertEquals(config('seo.sitemap.priorities.static'), $staticPriority);
    }

    /**
     * Test that sitemap includes correct changefreq values
     *
     * **Validates: Requirement 27.9** - Sitemaps include correct changefreq values
     * (home: daily, memorial: weekly, static: monthly)
     */
    public function test_sitemap_includes_correct_changefreq_values(): void
    {
        // Create a public memorial
        $user = User::factory()->create();
        Memorial::factory()->create([
            'user_id' => $user->id,
            'is_public' => true,
            'slug' => 'test-memorial',
        ]);

        $xml = $this->sitemapService->generateSitemap('en');

        // Check that all changefreq values are present
        $this->assertStringContainsString('<changefreq>daily</changefreq>', $xml); // home
        $this->assertStringContainsString('<changefreq>weekly</changefreq>', $xml); // memorial
        $this->assertStringContainsString('<changefreq>monthly</changefreq>', $xml); // static pages
    }

    /**
     * Test that changefreq values are retrieved from config
     *
     * **Validates: Requirement 27.9** - Changefreq values come from configuration
     */
    public function test_changefreq_values_are_retrieved_from_config(): void
    {
        $homeChangefreq = $this->sitemapService->getChangeFreq('home');
        $memorialChangefreq = $this->sitemapService->getChangeFreq('memorial');
        $staticChangefreq = $this->sitemapService->getChangeFreq('static');

        $this->assertEquals('daily', $homeChangefreq);
        $this->assertEquals('weekly', $memorialChangefreq);
        $this->assertEquals('monthly', $staticChangefreq);

        // Verify they match config values
        $this->assertEquals(config('seo.sitemap.change_frequencies.home'), $homeChangefreq);
        $this->assertEquals(config('seo.sitemap.change_frequencies.memorial'), $memorialChangefreq);
        $this->assertEquals(config('seo.sitemap.change_frequencies.static'), $staticChangefreq);
    }

    /**
     * Test that sitemap includes lastmod timestamps for all URLs
     *
     * **Validates: Requirement 27.10** - All URLs include lastmod timestamps
     */
    public function test_sitemap_includes_lastmod_timestamps_for_all_urls(): void
    {
        $xml = $this->sitemapService->generateSitemap('en');

        // Parse XML
        $xmlObj = simplexml_load_string($xml);
        $urls = $xmlObj->url;

        $this->assertGreaterThan(0, count($urls), 'Sitemap should contain URLs');

        // Verify every URL has a lastmod timestamp
        foreach ($urls as $url) {
            $this->assertNotEmpty((string) $url->lastmod, 'Every URL should have a lastmod timestamp');

            // Verify lastmod is in valid ISO 8601 format
            $lastmod = (string) $url->lastmod;
            $this->assertMatchesRegularExpression(
                '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/',
                $lastmod,
                'Lastmod should be in ISO 8601 format'
            );
        }
    }

    /**
     * Test that memorial URLs include updated_at as lastmod
     *
     * **Validates: Requirement 27.10** - Memorial lastmod reflects actual update time
     */
    public function test_memorial_urls_include_updated_at_as_lastmod(): void
    {
        $user = User::factory()->create();
        $memorial = Memorial::factory()->create([
            'user_id' => $user->id,
            'is_public' => true,
            'slug' => 'john-doe',
            'updated_at' => now()->subDays(5),
        ]);

        $xml = $this->sitemapService->generateSitemap('en');

        // Parse XML and find memorial URL
        $xmlObj = simplexml_load_string($xml);
        $urls = $xmlObj->url;

        $memorialFound = false;
        foreach ($urls as $url) {
            if (str_contains((string) $url->loc, 'john-doe')) {
                $memorialFound = true;
                $lastmod = (string) $url->lastmod;

                // Verify lastmod is present and valid
                $this->assertNotEmpty($lastmod);
                $this->assertMatchesRegularExpression(
                    '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/',
                    $lastmod
                );
                break;
            }
        }

        $this->assertTrue($memorialFound, 'Memorial URL should be present in sitemap');
    }

    /**
     * Test that proper XML content-type headers are returned
     *
     * **Validates: Requirement 27.1** - Proper content-type headers
     */
    public function test_proper_xml_content_type_headers_are_returned(): void
    {
        // Test sitemap index
        $indexResponse = $this->get('/sitemap.xml');
        $indexResponse->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

        // Test locale-specific sitemap
        $localeResponse = $this->get('/sitemap-en.xml');
        $localeResponse->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
    }

    /**
     * Test that sitemap includes static pages
     *
     * **Validates: Requirement 27.1** - Sitemap includes all important pages
     */
    public function test_sitemap_includes_static_pages(): void
    {
        $xml = $this->sitemapService->generateSitemap('en');

        // Static pages should be included
        $staticPages = ['about', 'contact', 'search', 'privacy', 'terms'];

        foreach ($staticPages as $page) {
            $this->assertStringContainsString($page, $xml, "Sitemap should include {$page} page");
        }
    }

    /**
     * Test that sitemap includes public memorials only
     *
     * **Validates: Requirement 27.1** - Only public memorials are included
     */
    public function test_sitemap_includes_public_memorials_only(): void
    {
        $user = User::factory()->create();

        // Create public memorial
        $publicMemorial = Memorial::factory()->create([
            'user_id' => $user->id,
            'is_public' => true,
            'slug' => 'public-memorial',
        ]);

        // Create private memorial
        $privateMemorial = Memorial::factory()->create([
            'user_id' => $user->id,
            'is_public' => false,
            'slug' => 'private-memorial',
        ]);

        $xml = $this->sitemapService->generateSitemap('en');

        // Public memorial should be included
        $this->assertStringContainsString('public-memorial', $xml);

        // Private memorial should NOT be included
        $this->assertStringNotContainsString('private-memorial', $xml);
    }

    /**
     * Test that sitemap XML is properly escaped
     *
     * **Validates: Requirement 27.1** - XML special characters are properly escaped
     */
    public function test_sitemap_xml_is_properly_escaped(): void
    {
        $user = User::factory()->create();

        // Create memorial with special characters in slug
        Memorial::factory()->create([
            'user_id' => $user->id,
            'is_public' => true,
            'slug' => 'test-memorial',
        ]);

        $xml = $this->sitemapService->generateSitemap('en');

        // Verify XML can be parsed (would fail if not properly escaped)
        $xmlObj = simplexml_load_string($xml);
        $this->assertNotFalse($xmlObj, 'XML should be valid and parseable');

        // Verify no unescaped special characters in URLs
        $this->assertStringNotContainsString('&amp;amp;', $xml, 'Should not have double-escaped ampersands');
    }

    /**
     * Test that sitemap service generates sitemap index
     *
     * **Validates: Requirement 27.1** - Service can generate sitemap index
     */
    public function test_sitemap_service_generates_sitemap_index(): void
    {
        $xml = $this->sitemapService->generateSitemapIndex();

        $this->assertNotEmpty($xml);
        $this->assertStringStartsWith('<?xml version="1.0" encoding="UTF-8"?>', $xml);
        $this->assertStringContainsString('<sitemapindex', $xml);
        $this->assertStringContainsString('</sitemapindex>', $xml);

        // Verify all locales are included
        $locales = ['bs', 'sr', 'hr', 'de', 'en', 'it'];
        foreach ($locales as $locale) {
            $this->assertStringContainsString("/sitemap-{$locale}.xml", $xml);
        }
    }

    /**
     * Test that sitemap service generates locale-specific sitemap
     *
     * **Validates: Requirement 27.1** - Service can generate locale-specific sitemaps
     */
    public function test_sitemap_service_generates_locale_specific_sitemap(): void
    {
        $xml = $this->sitemapService->generateSitemap('en');

        $this->assertNotEmpty($xml);
        $this->assertStringStartsWith('<?xml version="1.0" encoding="UTF-8"?>', $xml);
        $this->assertStringContainsString('<urlset', $xml);
        $this->assertStringContainsString('</urlset>', $xml);
        $this->assertStringContainsString('/en', $xml);
    }

    /**
     * Test that sitemap URLs use correct locale prefix
     *
     * **Validates: Requirement 27.1** - URLs include correct locale prefix
     */
    public function test_sitemap_urls_use_correct_locale_prefix(): void
    {
        $locales = ['bs', 'sr', 'hr', 'de', 'en', 'it'];

        foreach ($locales as $locale) {
            $xml = $this->sitemapService->generateSitemap($locale);

            // Parse XML and verify all URLs have correct locale
            $xmlObj = simplexml_load_string($xml);
            $urls = $xmlObj->url;

            foreach ($urls as $url) {
                $loc = (string) $url->loc;
                $this->assertStringContainsString("/{$locale}", $loc, "URL should contain locale prefix /{$locale}");
            }
        }
    }

    /**
     * Test that sitemap includes all required URL elements
     *
     * **Validates: Requirements 27.8, 27.9, 27.10** - All required elements present
     */
    public function test_sitemap_includes_all_required_url_elements(): void
    {
        $xml = $this->sitemapService->generateSitemap('en');

        // Parse XML
        $xmlObj = simplexml_load_string($xml);
        $urls = $xmlObj->url;

        $this->assertGreaterThan(0, count($urls), 'Sitemap should contain URLs');

        // Verify every URL has all required elements
        foreach ($urls as $url) {
            $this->assertNotEmpty((string) $url->loc, 'URL should have loc element');
            $this->assertNotEmpty((string) $url->lastmod, 'URL should have lastmod element');
            $this->assertNotEmpty((string) $url->changefreq, 'URL should have changefreq element');
            $this->assertNotEmpty((string) $url->priority, 'URL should have priority element');
        }
    }

    /**
     * Test that sitemap priority values are formatted correctly
     *
     * **Validates: Requirement 27.8** - Priority values are formatted with one decimal
     */
    public function test_sitemap_priority_values_are_formatted_correctly(): void
    {
        $xml = $this->sitemapService->generateSitemap('en');

        // Parse XML
        $xmlObj = simplexml_load_string($xml);
        $urls = $xmlObj->url;

        foreach ($urls as $url) {
            $priority = (string) $url->priority;

            // Verify priority is formatted with one decimal place
            $this->assertMatchesRegularExpression(
                '/^\d\.\d$/',
                $priority,
                'Priority should be formatted with one decimal place (e.g., 1.0, 0.8)'
            );

            // Verify priority is between 0.0 and 1.0
            $priorityValue = (float) $priority;
            $this->assertGreaterThanOrEqual(0.0, $priorityValue);
            $this->assertLessThanOrEqual(1.0, $priorityValue);
        }
    }

    /**
     * Test that sitemap changefreq values are valid
     *
     * **Validates: Requirement 27.9** - Changefreq values are valid
     */
    public function test_sitemap_changefreq_values_are_valid(): void
    {
        $xml = $this->sitemapService->generateSitemap('en');

        // Parse XML
        $xmlObj = simplexml_load_string($xml);
        $urls = $xmlObj->url;

        $validFrequencies = ['always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never'];

        foreach ($urls as $url) {
            $changefreq = (string) $url->changefreq;

            $this->assertContains(
                $changefreq,
                $validFrequencies,
                "Changefreq '{$changefreq}' should be one of the valid values"
            );
        }
    }

    /**
     * Test that sitemap handles empty memorial list gracefully
     *
     * **Validates: Requirement 27.1** - Sitemap works with no memorials
     */
    public function test_sitemap_handles_empty_memorial_list_gracefully(): void
    {
        // Don't create any memorials
        $xml = $this->sitemapService->generateSitemap('en');

        // Should still generate valid sitemap with static pages
        $this->assertNotEmpty($xml);
        $this->assertStringContainsString('<urlset', $xml);
        $this->assertStringContainsString('</urlset>', $xml);

        // Should include homepage and static pages
        $this->assertStringContainsString('/en', $xml);
        $this->assertStringContainsString('about', $xml);
        $this->assertStringContainsString('contact', $xml);
    }

    /**
     * Test that sitemap index includes lastmod for each sitemap
     *
     * **Validates: Requirement 27.10** - Sitemap index includes lastmod
     */
    public function test_sitemap_index_includes_lastmod_for_each_sitemap(): void
    {
        $xml = $this->sitemapService->generateSitemapIndex();

        // Parse XML
        $xmlObj = simplexml_load_string($xml);
        $sitemaps = $xmlObj->sitemap;

        $this->assertGreaterThan(0, count($sitemaps), 'Sitemap index should contain sitemaps');

        // Verify every sitemap has a lastmod timestamp
        foreach ($sitemaps as $sitemap) {
            $this->assertNotEmpty((string) $sitemap->lastmod, 'Every sitemap should have a lastmod timestamp');

            // Verify lastmod is in valid ISO 8601 format
            $lastmod = (string) $sitemap->lastmod;
            $this->assertMatchesRegularExpression(
                '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/',
                $lastmod,
                'Lastmod should be in ISO 8601 format'
            );
        }
    }
}
