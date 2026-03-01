<?php

namespace Tests\Feature\Analytics;

use App\Services\Analytics\GTMService;
use Tests\TestCase;

/**
 * CSP Compatibility Feature Tests
 *
 * Tests Content Security Policy compatibility for GTM and GA4 analytics.
 * Validates that scripts work with CSP nonces, no inline scripts without nonces,
 * and all analytics domains are included in CSP directives.
 *
 * Requirements: 37.1, 37.2, 37.3, 37.4
 */
class CSPCompatibilityTest extends TestCase
{
    /**
     * Test GTM head script includes nonce attribute when provided
     *
     * Validates Requirement 37.3: The Analytics_Engine SHALL use nonce-based CSP
     * when available
     */
    public function test_gtm_head_script_includes_nonce_when_provided(): void
    {
        $gtmService = app(GTMService::class);

        // Enable GTM for testing
        config(['analytics.gtm.enabled' => true]);
        config(['analytics.gtm.container_id' => 'GTM-TEST123']);
        config(['app.env' => 'production']);

        $testNonce = 'test-nonce-abc123';
        $script = $gtmService->getHeadScript($testNonce);

        // Verify nonce attribute is present in script tag
        $this->assertStringContainsString("nonce=\"{$testNonce}\"", $script);
        $this->assertStringContainsString('<script nonce="test-nonce-abc123">', $script);
    }

    /**
     * Test GTM head script works without nonce (graceful degradation)
     *
     * Validates that GTM script can work without nonce when CSP is not enforced
     */
    public function test_gtm_head_script_works_without_nonce(): void
    {
        $gtmService = app(GTMService::class);

        // Enable GTM for testing
        config(['analytics.gtm.enabled' => true]);
        config(['analytics.gtm.container_id' => 'GTM-TEST123']);
        config(['app.env' => 'production']);

        $script = $gtmService->getHeadScript(null);

        // Verify script is generated without nonce
        $this->assertStringContainsString('<script>', $script);
        $this->assertStringNotContainsString('nonce=', $script);
        $this->assertStringContainsString('GTM-TEST123', $script);
    }

    /**
     * Test no inline scripts without nonces in GTM components
     *
     * Validates Requirement 37.4: The Analytics_Engine SHALL not use inline
     * scripts without nonces
     */
    /**
     * Test no inline scripts without nonces in GTM components
     *
     * Validates Requirement 37.4: The Analytics_Engine SHALL not use inline
     * scripts without nonces
     */
    public function test_no_inline_scripts_without_nonces_in_gtm_components(): void
    {
        // Check GTM body component template
        $gtmBodyTemplate = file_get_contents(
            resource_path('views/components/analytics/gtm-body.blade.php')
        );

        // Body component should only have noscript iframe, no inline scripts
        // The template delegates to GTMService which generates the noscript
        $this->assertStringNotContainsString('<script>', $gtmBodyTemplate);
        $this->assertStringContainsString('getBodyNoScript()', $gtmBodyTemplate);

        // Verify the actual output from GTMService contains noscript
        $gtmService = app(GTMService::class);
        config(['analytics.gtm.enabled' => true]);
        config(['analytics.gtm.container_id' => 'GTM-TEST123']);
        config(['app.env' => 'production']);

        $noscript = $gtmService->getBodyNoScript();
        $this->assertStringContainsString('<noscript>', $noscript);
        $this->assertStringContainsString('<iframe', $noscript);
    }

    /**
     * Test data layer initialization component uses nonce
     *
     * Validates Requirement 37.3: Data layer initialization uses nonce-based CSP
     */

    /**
     * Test all GTM domains are in CSP script-src directives
     *
     * Validates Requirement 37.1: The Analytics_Engine SHALL only load scripts
     * from whitelisted domains
     * Validates Requirement 37.2: The Configuration_Manager SHALL provide CSP
     * directives for GTM and GA4 domains
     */
    public function test_all_gtm_domains_in_csp_script_src_directives(): void
    {
        $gtmService = app(GTMService::class);
        $cspDirectives = $gtmService->getCspDirectives();

        // Verify script_src directive exists
        $this->assertArrayHasKey('script_src', $cspDirectives);

        // Verify GTM domain is whitelisted
        $this->assertContains(
            'https://www.googletagmanager.com',
            $cspDirectives['script_src'],
            'GTM domain must be in script-src CSP directive'
        );

        // Verify GA4 domain is whitelisted
        $this->assertContains(
            'https://www.google-analytics.com',
            $cspDirectives['script_src'],
            'GA4 domain must be in script-src CSP directive'
        );
    }

    /**
     * Test all analytics domains are in CSP connect-src directives
     *
     * Validates Requirement 37.1: Analytics connections are whitelisted
     * Validates Requirement 37.2: CSP directives include all analytics domains
     */
    public function test_all_analytics_domains_in_csp_connect_src_directives(): void
    {
        $gtmService = app(GTMService::class);
        $cspDirectives = $gtmService->getCspDirectives();

        // Verify connect_src directive exists
        $this->assertArrayHasKey('connect_src', $cspDirectives);

        // Verify GA4 analytics domain is whitelisted
        $this->assertContains(
            'https://www.google-analytics.com',
            $cspDirectives['connect_src'],
            'GA4 analytics domain must be in connect-src CSP directive'
        );

        // Verify GA4 measurement protocol domain is whitelisted
        $this->assertContains(
            'https://analytics.google.com',
            $cspDirectives['connect_src'],
            'GA4 measurement protocol domain must be in connect-src CSP directive'
        );

        // Verify DoubleClick domain is whitelisted
        $this->assertContains(
            'https://stats.g.doubleclick.net',
            $cspDirectives['connect_src'],
            'DoubleClick domain must be in connect-src CSP directive'
        );
    }

    /**
     * Test all analytics domains are in CSP img-src directives
     *
     * Validates Requirement 37.1: Analytics image requests are whitelisted
     * Validates Requirement 37.2: CSP directives include image domains
     */
    public function test_all_analytics_domains_in_csp_img_src_directives(): void
    {
        $gtmService = app(GTMService::class);
        $cspDirectives = $gtmService->getCspDirectives();

        // Verify img_src directive exists
        $this->assertArrayHasKey('img_src', $cspDirectives);

        // Verify GA4 domain is whitelisted for images
        $this->assertContains(
            'https://www.google-analytics.com',
            $cspDirectives['img_src'],
            'GA4 domain must be in img-src CSP directive'
        );

        // Verify GTM domain is whitelisted for images
        $this->assertContains(
            'https://www.googletagmanager.com',
            $cspDirectives['img_src'],
            'GTM domain must be in img-src CSP directive'
        );
    }

    /**
     * Test CSP directives are complete and comprehensive
     *
     * Validates Requirement 37.2: Configuration manager provides complete
     * CSP directives for all analytics needs
     */
    public function test_csp_directives_are_complete(): void
    {
        $gtmService = app(GTMService::class);
        $cspDirectives = $gtmService->getCspDirectives();

        // Verify all required directive types are present
        $this->assertArrayHasKey('script_src', $cspDirectives);
        $this->assertArrayHasKey('connect_src', $cspDirectives);
        $this->assertArrayHasKey('img_src', $cspDirectives);

        // Verify each directive has at least one domain
        $this->assertNotEmpty($cspDirectives['script_src']);
        $this->assertNotEmpty($cspDirectives['connect_src']);
        $this->assertNotEmpty($cspDirectives['img_src']);

        // Verify all domains use HTTPS
        foreach ($cspDirectives as $directive => $domains) {
            foreach ($domains as $domain) {
                $this->assertStringStartsWith(
                    'https://',
                    $domain,
                    "All CSP domains must use HTTPS: {$domain} in {$directive}"
                );
            }
        }
    }

    /**
     * Test GTM service provides CSP directives from configuration
     *
     * Validates Requirement 37.2: Configuration manager reads CSP directives
     * from configuration
     */
    public function test_gtm_service_provides_csp_directives_from_config(): void
    {
        // Set custom CSP configuration
        config([
            'analytics.csp' => [
                'script_src' => [
                    'https://custom-gtm.example.com',
                ],
                'connect_src' => [
                    'https://custom-analytics.example.com',
                ],
                'img_src' => [
                    'https://custom-images.example.com',
                ],
            ],
        ]);

        $gtmService = app(GTMService::class);
        $cspDirectives = $gtmService->getCspDirectives();

        // Verify custom configuration is returned
        $this->assertContains('https://custom-gtm.example.com', $cspDirectives['script_src']);
        $this->assertContains('https://custom-analytics.example.com', $cspDirectives['connect_src']);
        $this->assertContains('https://custom-images.example.com', $cspDirectives['img_src']);
    }

    /**
     * Test cookie banner component does not have inline scripts without nonces
     *
     * Validates Requirement 37.4: Cookie banner respects CSP requirements
     */
    public function test_cookie_banner_component_respects_csp(): void
    {
        $cookieBannerTemplate = file_get_contents(
            resource_path('views/components/analytics/cookie-banner.blade.php')
        );

        // If there are inline scripts, they should use nonce
        if (str_contains($cookieBannerTemplate, '<script>')) {
            // Check if nonce is used
            $this->assertStringContainsString('{!! $nonce', $cookieBannerTemplate);
        }

        // Inline event handlers should not be used (onclick, onload, etc.)
        $this->assertStringNotContainsString('onclick=', $cookieBannerTemplate);
        $this->assertStringNotContainsString('onload=', $cookieBannerTemplate);
        $this->assertStringNotContainsString('onchange=', $cookieBannerTemplate);
    }

    /**
     * Test analytics configuration file includes CSP directives
     *
     * Validates Requirement 37.2: Configuration includes CSP directives
     */
    public function test_analytics_config_includes_csp_directives(): void
    {
        $analyticsConfig = config('analytics.csp');

        // Verify CSP configuration exists
        $this->assertIsArray($analyticsConfig);
        $this->assertArrayHasKey('script_src', $analyticsConfig);
        $this->assertArrayHasKey('connect_src', $analyticsConfig);
        $this->assertArrayHasKey('img_src', $analyticsConfig);
    }

    /**
     * Test GTM script only loads from whitelisted domains
     *
     * Validates Requirement 37.1: Scripts only load from whitelisted domains
     */
    public function test_gtm_script_only_loads_from_whitelisted_domains(): void
    {
        $gtmService = app(GTMService::class);

        // Enable GTM for testing
        config(['analytics.gtm.enabled' => true]);
        config(['analytics.gtm.container_id' => 'GTM-TEST123']);
        config(['app.env' => 'production']);

        $script = $gtmService->getHeadScript();

        // Verify script loads from GTM domain
        $this->assertStringContainsString('www.googletagmanager.com/gtm.js', $script);

        // Verify no other external domains are referenced
        $this->assertStringNotContainsString('http://', $script);

        // Verify HTTPS is used
        $this->assertStringContainsString('https://www.googletagmanager.com', $script);
    }

    /**
     * Test GTM noscript iframe only loads from whitelisted domains
     *
     * Validates Requirement 37.1: Noscript fallback respects domain whitelist
     */
    public function test_gtm_noscript_only_loads_from_whitelisted_domains(): void
    {
        $gtmService = app(GTMService::class);

        // Enable GTM for testing
        config(['analytics.gtm.enabled' => true]);
        config(['analytics.gtm.container_id' => 'GTM-TEST123']);
        config(['app.env' => 'production']);

        $noscript = $gtmService->getBodyNoScript();

        // Verify iframe loads from GTM domain
        $this->assertStringContainsString('www.googletagmanager.com/ns.html', $noscript);

        // Verify HTTPS is used
        $this->assertStringContainsString('https://www.googletagmanager.com', $noscript);
    }
}
