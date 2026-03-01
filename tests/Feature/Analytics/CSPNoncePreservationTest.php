<?php

namespace Tests\Feature\Analytics;

use Tests\TestCase;
use App\Services\Analytics\GTMService;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * CSP Nonce Preservation Property Tests
 *
 * **IMPORTANT**: These tests follow observation-first methodology
 * **GOAL**: Capture baseline behavior on UNFIXED code for non-buggy inputs
 * **EXPECTED OUTCOME**: Tests PASS on unfixed code (confirms baseline behavior to preserve)
 *
 * **Validates: Property 2 (Preservation) - Existing GTM and CSP Functionality**
 * **Validates: Requirements 3.1, 3.2, 3.3, 3.4**
 *
 * These tests verify that GTM operations and CSP configuration that do NOT involve
 * the csp_nonce() function continue to work correctly after the fix.
 */
class CSPNoncePreservationTest extends TestCase
{
    /**
     * Property: GTMService::getHeadScript(null) generates script tags without nonce attributes
     *
     * This property-based test verifies that when GTMService is called with null nonce,
     * it generates valid GTM script tags without nonce attributes across various configurations.
     *
     * **Validates: Requirements 3.1**
     */
    public function test_gtm_service_get_head_script_with_null_nonce_generates_valid_scripts(): void
    {
        // Property: For all valid GTM configurations, getHeadScript(null) produces valid HTML without nonce
        $testCases = [
            ['enabled' => true, 'container_id' => 'GTM-TEST123', 'env' => 'production'],
            ['enabled' => true, 'container_id' => 'GTM-PROD456', 'env' => 'staging'],
            ['enabled' => true, 'container_id' => 'GTM-ABC123', 'env' => 'production'],
            ['enabled' => false, 'container_id' => 'GTM-TEST123', 'env' => 'production'],
            ['enabled' => true, 'container_id' => 'GTM-TEST123', 'env' => 'local'],
        ];

        foreach ($testCases as $testCase) {
            // Arrange: Configure GTM with test case parameters
            config(['analytics.gtm.enabled' => $testCase['enabled']]);
            config(['analytics.gtm.container_id' => $testCase['container_id']]);
            config(['app.env' => $testCase['env']]);

            $gtmService = app(GTMService::class);

            // Act: Call getHeadScript with null nonce
            $script = $gtmService->getHeadScript(null);

            // Assert: Verify expected behavior based on configuration
            if ($testCase['enabled'] && $testCase['env'] !== 'local') {
                // GTM should be enabled - verify script is generated
                $this->assertStringContainsString('<script>', $script);
                $this->assertStringContainsString('googletagmanager.com', $script);
                $this->assertStringContainsString($testCase['container_id'], $script);
                $this->assertStringContainsString('window', $script);
                $this->assertStringContainsString('dataLayer', $script);

                // Verify NO nonce attribute is present
                $this->assertStringNotContainsString('nonce=', $script);
            } else {
                // GTM should be disabled - verify empty string
                $this->assertEmpty($script);
            }
        }
    }

    /**
     * Property: GTMService::getBodyNoScript() generates noscript iframe tags correctly
     *
     * This property-based test verifies that getBodyNoScript() generates valid noscript
     * iframe tags across various configurations.
     *
     * **Validates: Requirements 3.2**
     */
    public function test_gtm_service_get_body_noscript_generates_valid_iframes(): void
    {
        // Property: For all valid GTM configurations, getBodyNoScript() produces valid HTML
        $testCases = [
            ['enabled' => true, 'container_id' => 'GTM-TEST123', 'env' => 'production'],
            ['enabled' => true, 'container_id' => 'GTM-PROD456', 'env' => 'staging'],
            ['enabled' => true, 'container_id' => 'GTM-XYZ789', 'env' => 'production'],
            ['enabled' => false, 'container_id' => 'GTM-TEST123', 'env' => 'production'],
            ['enabled' => true, 'container_id' => 'GTM-TEST123', 'env' => 'local'],
        ];

        foreach ($testCases as $testCase) {
            // Arrange: Configure GTM with test case parameters
            config(['analytics.gtm.enabled' => $testCase['enabled']]);
            config(['analytics.gtm.container_id' => $testCase['container_id']]);
            config(['app.env' => $testCase['env']]);

            $gtmService = app(GTMService::class);

            // Act: Call getBodyNoScript
            $noscript = $gtmService->getBodyNoScript();

            // Assert: Verify expected behavior based on configuration
            if ($testCase['enabled'] && $testCase['env'] !== 'local') {
                // GTM should be enabled - verify noscript is generated
                $this->assertStringContainsString('<noscript>', $noscript);
                $this->assertStringContainsString('</noscript>', $noscript);
                $this->assertStringContainsString('<iframe', $noscript);
                $this->assertStringContainsString('googletagmanager.com/ns.html', $noscript);
                $this->assertStringContainsString($testCase['container_id'], $noscript);
                $this->assertStringContainsString('display:none', $noscript);
            } else {
                // GTM should be disabled - verify empty string
                $this->assertEmpty($noscript);
            }
        }
    }

    /**
     * Property: GTMService::getCspDirectives() returns correct CSP directives
     *
     * This property-based test verifies that getCspDirectives() returns the expected
     * CSP directives for GTM domains across various configurations.
     *
     * **Validates: Requirements 3.3**
     */
    public function test_gtm_service_get_csp_directives_returns_correct_directives(): void
    {
        // Property: For all configurations, getCspDirectives() returns consistent CSP directives
        $testCases = [
            ['enabled' => true, 'container_id' => 'GTM-TEST123', 'env' => 'production'],
            ['enabled' => false, 'container_id' => 'GTM-TEST123', 'env' => 'production'],
            ['enabled' => true, 'container_id' => 'GTM-TEST123', 'env' => 'local'],
        ];

        foreach ($testCases as $testCase) {
            // Arrange: Configure GTM with test case parameters
            config(['analytics.gtm.enabled' => $testCase['enabled']]);
            config(['analytics.gtm.container_id' => $testCase['container_id']]);
            config(['app.env' => $testCase['env']]);

            $gtmService = app(GTMService::class);

            // Act: Get CSP directives
            $directives = $gtmService->getCspDirectives();

            // Assert: Verify CSP directives structure and content
            $this->assertIsArray($directives);
            $this->assertArrayHasKey('script_src', $directives);
            $this->assertArrayHasKey('connect_src', $directives);
            $this->assertArrayHasKey('img_src', $directives);

            // Verify script_src contains GTM domains
            $this->assertContains('https://www.googletagmanager.com', $directives['script_src']);
            $this->assertContains('https://www.google-analytics.com', $directives['script_src']);

            // Verify connect_src contains analytics domains
            $this->assertContains('https://www.google-analytics.com', $directives['connect_src']);
            $this->assertContains('https://analytics.google.com', $directives['connect_src']);
            $this->assertContains('https://stats.g.doubleclick.net', $directives['connect_src']);

            // Verify img_src contains tracking domains
            $this->assertContains('https://www.google-analytics.com', $directives['img_src']);
            $this->assertContains('https://www.googletagmanager.com', $directives['img_src']);
        }
    }

    /**
     * Property: SecurityHeaders middleware applies CSP policy with 'unsafe-inline' for scripts
     *
     * This property-based test verifies that the SecurityHeaders middleware continues to
     * apply the CSP policy with 'unsafe-inline' for scripts across various requests.
     *
     * **Validates: Requirements 3.3**
     */
    public function test_security_headers_middleware_applies_csp_with_unsafe_inline(): void
    {
        // Property: For all requests, SecurityHeaders applies consistent CSP policy
        $testCases = [
            ['method' => 'GET', 'uri' => '/'],
            ['method' => 'GET', 'uri' => '/about'],
            ['method' => 'POST', 'uri' => '/contact'],
            ['method' => 'GET', 'uri' => '/analytics'],
        ];

        foreach ($testCases as $testCase) {
            // Arrange: Create request and middleware
            $request = Request::create($testCase['uri'], $testCase['method']);
            $middleware = new SecurityHeaders();

            // Act: Process request through middleware
            $response = $middleware->handle($request, function ($req) {
                return new Response('Test content');
            });

            // Assert: Verify CSP header is set correctly
            $this->assertTrue($response->headers->has('Content-Security-Policy'));

            $cspHeader = $response->headers->get('Content-Security-Policy');

            // Verify CSP policy contains 'unsafe-inline' for scripts
            $this->assertStringContainsString("script-src 'self' 'unsafe-inline' 'unsafe-eval'", $cspHeader);

            // Verify other CSP directives are present
            $this->assertStringContainsString("default-src 'self'", $cspHeader);
            $this->assertStringContainsString("style-src 'self' 'unsafe-inline'", $cspHeader);
            $this->assertStringContainsString("img-src 'self' data: https:", $cspHeader);

            // Verify other security headers are present
            $this->assertTrue($response->headers->has('Strict-Transport-Security'));
            $this->assertTrue($response->headers->has('X-Frame-Options'));
            $this->assertTrue($response->headers->has('X-Content-Type-Options'));
            $this->assertTrue($response->headers->has('Referrer-Policy'));
            $this->assertTrue($response->headers->has('Permissions-Policy'));
        }
    }

    /**
     * Property: GTMService isEnabled() logic works correctly across environments
     *
     * This property-based test verifies that the GTM enable/disable logic based on
     * environment and configuration continues to work correctly.
     *
     * **Validates: Requirements 3.1, 3.2**
     */
    public function test_gtm_service_is_enabled_logic_works_correctly(): void
    {
        // Property: For all configurations, isEnabled() returns correct boolean
        $testCases = [
            ['enabled' => true, 'container_id' => 'GTM-TEST123', 'env' => 'production', 'expected' => true],
            ['enabled' => true, 'container_id' => 'GTM-TEST123', 'env' => 'staging', 'expected' => true],
            ['enabled' => true, 'container_id' => 'GTM-TEST123', 'env' => 'local', 'expected' => false],
            ['enabled' => false, 'container_id' => 'GTM-TEST123', 'env' => 'production', 'expected' => false],
            ['enabled' => true, 'container_id' => '', 'env' => 'production', 'expected' => false],
            ['enabled' => true, 'container_id' => null, 'env' => 'production', 'expected' => false],
        ];

        foreach ($testCases as $testCase) {
            // Arrange: Configure GTM with test case parameters
            config(['analytics.gtm.enabled' => $testCase['enabled']]);
            config(['analytics.gtm.container_id' => $testCase['container_id']]);
            config(['app.env' => $testCase['env']]);

            $gtmService = app(GTMService::class);

            // Act: Check if GTM is enabled
            $isEnabled = $gtmService->isEnabled();

            // Assert: Verify expected result
            $this->assertEquals($testCase['expected'], $isEnabled,
                "Failed for config: enabled={$testCase['enabled']}, container_id={$testCase['container_id']}, env={$testCase['env']}");
        }
    }

    /**
     * Property: GTMService getContainerId() returns correct value across environments
     *
     * This property-based test verifies that getContainerId() returns the correct
     * container ID or null based on environment.
     *
     * **Validates: Requirements 3.1, 3.2**
     */
    public function test_gtm_service_get_container_id_works_correctly(): void
    {
        // Property: For all configurations, getContainerId() returns correct value
        $testCases = [
            ['container_id' => 'GTM-TEST123', 'env' => 'production', 'expected' => 'GTM-TEST123'],
            ['container_id' => 'GTM-PROD456', 'env' => 'staging', 'expected' => 'GTM-PROD456'],
            ['container_id' => 'GTM-TEST123', 'env' => 'local', 'expected' => null],
            ['container_id' => '', 'env' => 'production', 'expected' => ''],
            ['container_id' => null, 'env' => 'production', 'expected' => null],
        ];

        foreach ($testCases as $testCase) {
            // Arrange: Configure GTM with test case parameters
            config(['analytics.gtm.container_id' => $testCase['container_id']]);
            config(['app.env' => $testCase['env']]);

            $gtmService = app(GTMService::class);

            // Act: Get container ID
            $containerId = $gtmService->getContainerId();

            // Assert: Verify expected result
            $this->assertEquals($testCase['expected'], $containerId,
                "Failed for config: container_id={$testCase['container_id']}, env={$testCase['env']}");
        }
    }
}
