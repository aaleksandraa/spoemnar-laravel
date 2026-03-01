<?php

namespace Tests\Feature\Analytics;

use Tests\TestCase;
use Illuminate\Support\Facades\View;
use App\Services\Analytics\GTMService;

/**
 * CSP Nonce Bug Condition Exploration Test
 *
 * **CRITICAL**: This test MUST FAIL on unfixed code - failure confirms the bug exists
 * **DO NOT attempt to fix the test or the code when it fails**
 * **NOTE**: This test encodes the expected behavior - it will validate the fix when it passes after implementation
 *
 * **Validates: Property 1 (Fault Condition) - Analytics Components Crash on csp_nonce() Call**
 * **Validates: Requirements 2.1, 2.2**
 *
 * This test surfaces counterexamples that demonstrate the bug exists by attempting to render
 * the analytics components that call the non-existent csp_nonce() function.
 */
class CSPNonceBugConditionTest extends TestCase
{
    /**
     * Test that data-layer-init.blade.php renders without fatal errors
     *
     * **EXPECTED OUTCOME ON UNFIXED CODE**: Test FAILS with "Call to undefined function csp_nonce()" error
     * **EXPECTED OUTCOME ON FIXED CODE**: Test PASSES - component renders successfully
     *
     * **Validates: Requirements 2.1**
     */
    public function test_data_layer_init_component_renders_without_csp_nonce_errors(): void
    {
        // Arrange: Prepare initial state data for the component
        $initialState = [
            'page_title' => 'Test Page',
            'user_id' => null,
            'memorial_id' => null,
        ];

        // Act: Attempt to render the component
        // On unfixed code, this will throw "Call to undefined function csp_nonce()" fatal error
        $rendered = View::make('components.analytics.data-layer-init', [
            'initialState' => $initialState,
        ])->render();

        // Assert: Component should render successfully without calling csp_nonce()
        $this->assertStringContainsString('<script>', $rendered);
        $this->assertStringContainsString('window.dataLayer', $rendered);
        $this->assertStringContainsString('"page_title":"Test Page"', $rendered);

        // Verify no nonce attribute is present (expected behavior after fix)
        $this->assertStringNotContainsString('nonce=', $rendered);
    }

    /**
     * Test that gtm-head.blade.php renders without fatal errors
     *
     * **EXPECTED OUTCOME ON UNFIXED CODE**: Test FAILS with "Call to undefined function csp_nonce()" error
     * **EXPECTED OUTCOME ON FIXED CODE**: Test PASSES - component renders successfully
     *
     * **Validates: Requirements 2.2**
     */
    public function test_gtm_head_component_renders_without_csp_nonce_errors(): void
    {
        // Arrange: Enable GTM for testing
        config(['analytics.gtm.enabled' => true]);
        config(['analytics.gtm.container_id' => 'GTM-TEST123']);
        config(['app.env' => 'production']);

        $gtmService = app(GTMService::class);

        // Act: Attempt to render the component
        // On unfixed code, this will throw "Call to undefined function csp_nonce()" fatal error
        $rendered = View::make('components.analytics.gtm-head', [
            'gtmService' => $gtmService,
        ])->render();

        // Assert: Component should render successfully without calling csp_nonce()
        $this->assertStringContainsString('googletagmanager.com', $rendered);
        $this->assertStringContainsString('GTM-TEST123', $rendered);

        // Verify GTM script is present
        $this->assertStringContainsString('<script>', $rendered);
    }

    /**
     * Test that CSPCompatibilityTest::test_data_layer_initialization_uses_nonce() expectations are met
     *
     * This test verifies that the data-layer-init.blade.php template can be read and analyzed
     * without expecting csp_nonce() to exist.
     *
     * **EXPECTED OUTCOME ON UNFIXED CODE**: Test FAILS because template contains csp_nonce() call
     * **EXPECTED OUTCOME ON FIXED CODE**: Test PASSES - template does not call csp_nonce()
     *
     * **Validates: Requirements 2.1**
     */
    public function test_data_layer_init_template_does_not_call_csp_nonce(): void
    {
        // Arrange: Read the template file
        $dataLayerTemplate = file_get_contents(
            resource_path('views/components/analytics/data-layer-init.blade.php')
        );

        // Assert: Template should not call csp_nonce() function
        // On unfixed code, this assertion will fail because the template contains csp_nonce()
        $this->assertStringNotContainsString('csp_nonce()', $dataLayerTemplate);

        // Verify template still has script tag (basic structure preserved)
        $this->assertStringContainsString('<script', $dataLayerTemplate);
        $this->assertStringContainsString('window.dataLayer', $dataLayerTemplate);
    }

    /**
     * Test that gtm-head.blade.php template does not call csp_nonce()
     *
     * **EXPECTED OUTCOME ON UNFIXED CODE**: Test FAILS because template contains csp_nonce() call
     * **EXPECTED OUTCOME ON FIXED CODE**: Test PASSES - template passes null to GTMService
     *
     * **Validates: Requirements 2.2**
     */
    public function test_gtm_head_template_does_not_call_csp_nonce(): void
    {
        // Arrange: Read the template file
        $gtmHeadTemplate = file_get_contents(
            resource_path('views/components/analytics/gtm-head.blade.php')
        );

        // Assert: Template should not call csp_nonce() function
        // On unfixed code, this assertion will fail because the template contains csp_nonce()
        $this->assertStringNotContainsString('csp_nonce()', $gtmHeadTemplate);

        // Verify template passes null to GTMService (expected behavior after fix)
        $this->assertStringContainsString('getHeadScript(null)', $gtmHeadTemplate);
    }

    /**
     * Test that GTMService::getHeadScript(null) works correctly
     *
     * This test confirms that the GTMService already handles null nonce values correctly,
     * which means the fix only needs to remove csp_nonce() calls and pass null instead.
     *
     * **EXPECTED OUTCOME**: This test should PASS on both unfixed and fixed code
     * (confirms GTMService doesn't need changes)
     *
     * **Validates: Requirements 2.2**
     */
    public function test_gtm_service_handles_null_nonce_correctly(): void
    {
        // Arrange: Enable GTM for testing
        config(['analytics.gtm.enabled' => true]);
        config(['analytics.gtm.container_id' => 'GTM-TEST123']);
        config(['app.env' => 'production']);

        $gtmService = app(GTMService::class);

        // Act: Call getHeadScript with null nonce
        $script = $gtmService->getHeadScript(null);

        // Assert: Script should be generated without nonce attribute
        $this->assertStringContainsString('<script>', $script);
        $this->assertStringNotContainsString('nonce=', $script);
        $this->assertStringContainsString('GTM-TEST123', $script);
        $this->assertStringContainsString('googletagmanager.com', $script);
    }
}
