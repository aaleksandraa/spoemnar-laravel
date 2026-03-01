<?php

namespace Tests\Feature\Analytics;

use Tests\TestCase;
use App\Services\Analytics\GTMService;

/**
 * GTM Component Variable Preservation Property Tests
 *
 * **IMPORTANT**: These tests follow observation-first methodology
 * **GOAL**: Capture baseline behavior on UNFIXED code for non-buggy inputs
 * **EXPECTED OUTCOME**: Tests PASS on unfixed code (confirms baseline behavior to preserve)
 *
 * **Validates: Property 2 (Preservation) - GTM Functionality Unchanged**
 * **Validates: Requirements 3.1, 3.2, 3.3, 3.4**
 *
 * These tests verify that GTM operations (enabled/disabled logic, container ID handling,
 * script output format) continue to work correctly after the fix. We test GTMService
 * directly (bypassing component rendering) to establish baseline behavior.
 */
class GTMComponentVariablePreservationTest extends TestCase
{
    /**
     * Property: GTM enabled with valid container ID → head script contains correct container ID
     *
     * This property-based test verifies that when GTM is enabled with a valid container ID,
     * the head script is generated correctly with the container ID across various configurations.
     *
     * **Validates: Requirements 3.1, 3.4**
     */
    public function test_gtm_enabled_with_valid_container_id_generates_correct_head_script(): void
    {
        // Property: For all valid GTM configurations with enabled=true, getHeadScript() produces correct output
        $testCases = [
            ['container_id' => 'GTM-TEST123', 'env' => 'production'],
            ['container_id' => 'GTM-PROD456', 'env' => 'staging'],
            ['container_id' => 'GTM-ABC123', 'env' => 'production'],
            ['container_id' => 'GTM-XYZ789', 'env' => 'staging'],
            ['container_id' => 'GTM-DEMO999', 'env' => 'production'],
        ];

        foreach ($testCases as $testCase) {
            // Arrange: Configure GTM with enabled=true and valid container ID
            config(['analytics.gtm.enabled' => true]);
            config(['analytics.gtm.container_id' => $testCase['container_id']]);
            config(['app.env' => $testCase['env']]);

            $gtmService = app(GTMService::class);

            // Act: Get head script
            $headScript = $gtmService->getHeadScript();

            // Assert: Verify head script contains correct container ID
            $this->assertStringContainsString($testCase['container_id'], $headScript,
                "Head script should contain container ID {$testCase['container_id']}");
            $this->assertStringContainsString('<script>', $headScript,
                "Head script should contain script tag");
            $this->assertStringContainsString('googletagmanager.com/gtm.js', $headScript,
                "Head script should contain GTM script URL");
            $this->assertStringContainsString('window', $headScript,
                "Head script should contain window reference");
            $this->assertStringContainsString('dataLayer', $headScript,
                "Head script should contain dataLayer reference");
            $this->assertStringContainsString("'gtm.start'", $headScript,
                "Head script should contain gtm.start event");

            // Verify it's a complete script tag
            $this->assertMatchesRegularExpression('/<script[^>]*>.*<\/script>/s', $headScript,
                "Head script should be a complete script tag");
        }
    }

    /**
     * Property: GTM enabled with valid container ID → body noscript contains correct container ID
     *
     * This property-based test verifies that when GTM is enabled with a valid container ID,
     * the body noscript is generated correctly with the container ID across various configurations.
     *
     * **Validates: Requirements 3.1, 3.4**
     */
    public function test_gtm_enabled_with_valid_container_id_generates_correct_body_noscript(): void
    {
        // Property: For all valid GTM configurations with enabled=true, getBodyNoScript() produces correct output
        $testCases = [
            ['container_id' => 'GTM-TEST123', 'env' => 'production'],
            ['container_id' => 'GTM-PROD456', 'env' => 'staging'],
            ['container_id' => 'GTM-ABC123', 'env' => 'production'],
            ['container_id' => 'GTM-XYZ789', 'env' => 'staging'],
            ['container_id' => 'GTM-DEMO999', 'env' => 'production'],
        ];

        foreach ($testCases as $testCase) {
            // Arrange: Configure GTM with enabled=true and valid container ID
            config(['analytics.gtm.enabled' => true]);
            config(['analytics.gtm.container_id' => $testCase['container_id']]);
            config(['app.env' => $testCase['env']]);

            $gtmService = app(GTMService::class);

            // Act: Get body noscript
            $bodyNoScript = $gtmService->getBodyNoScript();

            // Assert: Verify body noscript contains correct container ID
            $this->assertStringContainsString($testCase['container_id'], $bodyNoScript,
                "Body noscript should contain container ID {$testCase['container_id']}");
            $this->assertStringContainsString('<noscript>', $bodyNoScript,
                "Body noscript should contain noscript tag");
            $this->assertStringContainsString('</noscript>', $bodyNoScript,
                "Body noscript should close noscript tag");
            $this->assertStringContainsString('<iframe', $bodyNoScript,
                "Body noscript should contain iframe tag");
            $this->assertStringContainsString('googletagmanager.com/ns.html', $bodyNoScript,
                "Body noscript should contain GTM noscript URL");
            $this->assertStringContainsString('display:none', $bodyNoScript,
                "Body noscript iframe should be hidden");
            $this->assertStringContainsString('height="0"', $bodyNoScript,
                "Body noscript iframe should have height=0");
            $this->assertStringContainsString('width="0"', $bodyNoScript,
                "Body noscript iframe should have width=0");

            // Verify it's a complete noscript with iframe
            $this->assertMatchesRegularExpression('/<noscript>.*<iframe.*<\/noscript>/s', $bodyNoScript,
                "Body noscript should be a complete noscript tag with iframe");
        }
    }

    /**
     * Property: GTM disabled → no scripts output (empty strings)
     *
     * This property-based test verifies that when GTM is disabled,
     * both head script and body noscript return empty strings across various configurations.
     *
     * **Validates: Requirements 3.2, 3.4**
     */
    public function test_gtm_disabled_returns_empty_strings(): void
    {
        // Property: For all configurations with enabled=false, both methods return empty strings
        $testCases = [
            ['enabled' => false, 'container_id' => 'GTM-TEST123', 'env' => 'production'],
            ['enabled' => false, 'container_id' => 'GTM-PROD456', 'env' => 'staging'],
            ['enabled' => false, 'container_id' => '', 'env' => 'production'],
            ['enabled' => false, 'container_id' => null, 'env' => 'production'],
            ['enabled' => true, 'container_id' => '', 'env' => 'production'], // Empty container ID
            ['enabled' => true, 'container_id' => null, 'env' => 'production'], // Null container ID
        ];

        foreach ($testCases as $testCase) {
            // Arrange: Configure GTM with disabled or invalid configuration
            config(['analytics.gtm.enabled' => $testCase['enabled']]);
            config(['analytics.gtm.container_id' => $testCase['container_id']]);
            config(['app.env' => $testCase['env']]);

            $gtmService = app(GTMService::class);

            // Act: Get head script and body noscript
            $headScript = $gtmService->getHeadScript();
            $bodyNoScript = $gtmService->getBodyNoScript();

            // Assert: Both should return empty strings
            $this->assertEmpty($headScript,
                "Head script should be empty when GTM is disabled or container ID is invalid");
            $this->assertEmpty($bodyNoScript,
                "Body noscript should be empty when GTM is disabled or container ID is invalid");
        }
    }

    /**
     * Property: Local environment → GTM disabled regardless of config
     *
     * This property-based test verifies that in local environment,
     * GTM is always disabled regardless of enabled flag or container ID configuration.
     *
     * **Validates: Requirements 3.2, 3.4**
     */
    public function test_local_environment_disables_gtm_regardless_of_config(): void
    {
        // Property: For all configurations with env=local, GTM is disabled
        $testCases = [
            ['enabled' => true, 'container_id' => 'GTM-TEST123'],
            ['enabled' => true, 'container_id' => 'GTM-PROD456'],
            ['enabled' => false, 'container_id' => 'GTM-TEST123'],
            ['enabled' => true, 'container_id' => ''],
            ['enabled' => true, 'container_id' => null],
        ];

        foreach ($testCases as $testCase) {
            // Arrange: Configure GTM with local environment
            config(['analytics.gtm.enabled' => $testCase['enabled']]);
            config(['analytics.gtm.container_id' => $testCase['container_id']]);
            config(['app.env' => 'local']);

            $gtmService = app(GTMService::class);

            // Act: Check if GTM is enabled and get scripts
            $isEnabled = $gtmService->isEnabled();
            $containerId = $gtmService->getContainerId();
            $headScript = $gtmService->getHeadScript();
            $bodyNoScript = $gtmService->getBodyNoScript();

            // Assert: GTM should be disabled in local environment
            $this->assertFalse($isEnabled,
                "GTM should be disabled in local environment regardless of config");
            $this->assertNull($containerId,
                "Container ID should be null in local environment");
            $this->assertEmpty($headScript,
                "Head script should be empty in local environment");
            $this->assertEmpty($bodyNoScript,
                "Body noscript should be empty in local environment");
        }
    }

    /**
     * Property: Different container IDs → scripts contain correct IDs
     *
     * This property-based test verifies that different container IDs
     * are correctly reflected in the generated scripts.
     *
     * **Validates: Requirements 3.1, 3.4**
     */
    public function test_different_container_ids_generate_correct_scripts(): void
    {
        // Property: For all different container IDs, scripts contain the correct ID
        $containerIds = [
            'GTM-TEST123',
            'GTM-PROD456',
            'GTM-ABC123',
            'GTM-XYZ789',
            'GTM-DEMO999',
            'GTM-1234567',
            'GTM-ABCDEFG',
        ];

        foreach ($containerIds as $containerId) {
            // Arrange: Configure GTM with specific container ID
            config(['analytics.gtm.enabled' => true]);
            config(['analytics.gtm.container_id' => $containerId]);
            config(['app.env' => 'production']);

            $gtmService = app(GTMService::class);

            // Act: Get scripts
            $headScript = $gtmService->getHeadScript();
            $bodyNoScript = $gtmService->getBodyNoScript();

            // Assert: Both scripts should contain the correct container ID
            $this->assertStringContainsString($containerId, $headScript,
                "Head script should contain container ID {$containerId}");
            $this->assertStringContainsString($containerId, $bodyNoScript,
                "Body noscript should contain container ID {$containerId}");

            // Verify the container ID appears in the correct context
            $this->assertStringContainsString('gtm.js?id=', $headScript,
                "Head script should use container ID in GTM script URL");
            $this->assertStringContainsString("ns.html?id={$containerId}", $bodyNoScript,
                "Body noscript should use container ID in iframe URL");
        }
    }

    /**
     * Property: GTM isEnabled() logic works correctly across all configurations
     *
     * This property-based test verifies that the isEnabled() method
     * returns the correct boolean value based on configuration and environment.
     *
     * **Validates: Requirements 3.2, 3.4**
     */
    public function test_gtm_is_enabled_logic_works_correctly(): void
    {
        // Property: For all configurations, isEnabled() returns correct boolean
        $testCases = [
            ['enabled' => true, 'container_id' => 'GTM-TEST123', 'env' => 'production', 'expected' => true],
            ['enabled' => true, 'container_id' => 'GTM-PROD456', 'env' => 'staging', 'expected' => true],
            ['enabled' => true, 'container_id' => 'GTM-TEST123', 'env' => 'local', 'expected' => false],
            ['enabled' => false, 'container_id' => 'GTM-TEST123', 'env' => 'production', 'expected' => false],
            ['enabled' => true, 'container_id' => '', 'env' => 'production', 'expected' => false],
            ['enabled' => true, 'container_id' => null, 'env' => 'production', 'expected' => false],
            ['enabled' => false, 'container_id' => '', 'env' => 'local', 'expected' => false],
            ['enabled' => false, 'container_id' => null, 'env' => 'local', 'expected' => false],
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
     * Property: GTM getContainerId() returns correct value across all configurations
     *
     * This property-based test verifies that the getContainerId() method
     * returns the correct container ID or null based on environment.
     *
     * **Validates: Requirements 3.1, 3.4**
     */
    public function test_gtm_get_container_id_works_correctly(): void
    {
        // Property: For all configurations, getContainerId() returns correct value
        $testCases = [
            ['container_id' => 'GTM-TEST123', 'env' => 'production', 'expected' => 'GTM-TEST123'],
            ['container_id' => 'GTM-PROD456', 'env' => 'staging', 'expected' => 'GTM-PROD456'],
            ['container_id' => 'GTM-ABC123', 'env' => 'production', 'expected' => 'GTM-ABC123'],
            ['container_id' => 'GTM-TEST123', 'env' => 'local', 'expected' => null],
            ['container_id' => '', 'env' => 'production', 'expected' => ''],
            ['container_id' => null, 'env' => 'production', 'expected' => null],
            ['container_id' => '', 'env' => 'local', 'expected' => null],
            ['container_id' => null, 'env' => 'local', 'expected' => null],
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

    /**
     * Property: GTM script output format is preserved
     *
     * This property-based test verifies that the GTM script output format
     * (structure, HTML tags, JavaScript code) remains consistent.
     *
     * **Validates: Requirements 3.1, 3.3**
     */
    public function test_gtm_script_output_format_is_preserved(): void
    {
        // Property: For all enabled configurations, script format is consistent
        $testCases = [
            ['container_id' => 'GTM-TEST123', 'env' => 'production'],
            ['container_id' => 'GTM-PROD456', 'env' => 'staging'],
        ];

        foreach ($testCases as $testCase) {
            // Arrange: Configure GTM
            config(['analytics.gtm.enabled' => true]);
            config(['analytics.gtm.container_id' => $testCase['container_id']]);
            config(['app.env' => $testCase['env']]);

            $gtmService = app(GTMService::class);

            // Act: Get scripts
            $headScript = $gtmService->getHeadScript();
            $bodyNoScript = $gtmService->getBodyNoScript();

            // Assert: Verify head script format
            $this->assertStringContainsString('<!-- Google Tag Manager -->', $headScript,
                "Head script should contain GTM comment");
            $this->assertStringContainsString('<!-- End Google Tag Manager -->', $headScript,
                "Head script should contain end GTM comment");
            $this->assertStringContainsString('(function(w,d,s,l,i)', $headScript,
                "Head script should contain IIFE function signature");
            $this->assertStringContainsString("w[l]=w[l]||[]", $headScript,
                "Head script should initialize dataLayer");
            $this->assertStringContainsString("w[l].push({'gtm.start':", $headScript,
                "Head script should push gtm.start event");
            $this->assertStringContainsString('new Date().getTime()', $headScript,
                "Head script should include timestamp");
            $this->assertStringContainsString("event:'gtm.js'", $headScript,
                "Head script should include gtm.js event");
            $this->assertStringContainsString('j.async=true', $headScript,
                "Head script should set async=true");
            $this->assertStringContainsString('www.googletagmanager.com/gtm.js', $headScript,
                "Head script should include GTM script URL");

            // Assert: Verify body noscript format
            $this->assertStringContainsString('<!-- Google Tag Manager (noscript) -->', $bodyNoScript,
                "Body noscript should contain GTM noscript comment");
            $this->assertStringContainsString('<!-- End Google Tag Manager (noscript) -->', $bodyNoScript,
                "Body noscript should contain end GTM noscript comment");
            $this->assertStringContainsString('<noscript>', $bodyNoScript,
                "Body noscript should contain noscript tag");
            $this->assertStringContainsString('</noscript>', $bodyNoScript,
                "Body noscript should close noscript tag");
            $this->assertStringContainsString('<iframe', $bodyNoScript,
                "Body noscript should contain iframe tag");
            $this->assertStringContainsString('src="https://www.googletagmanager.com/ns.html', $bodyNoScript,
                "Body noscript should include GTM noscript URL");
            $this->assertStringContainsString('style="display:none;visibility:hidden"', $bodyNoScript,
                "Body noscript iframe should be hidden with inline styles");
            $this->assertStringContainsString('title="Google Tag Manager"', $bodyNoScript,
                "Body noscript iframe should have accessibility title");
        }
    }
}
