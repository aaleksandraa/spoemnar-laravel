<?php

namespace Tests\Feature\Analytics;

use Tests\TestCase;
use Illuminate\Support\Facades\View;
use App\Services\Analytics\GTMService;
use App\View\Components\Analytics\GTMHead;
use App\View\Components\Analytics\GTMBody;

/**
 * GTM Component Variable Bug Condition Exploration Test
 *
 * **CRITICAL**: This test MUST FAIL on unfixed code - failure confirms the bug exists
 * **DO NOT attempt to fix the test or the code when it fails**
 * **NOTE**: This test encodes the expected behavior - it will validate the fix when it passes after implementation
 *
 * **Validates: Property 1 (Fault Condition) - GTM Components Render Without Errors**
 * **Validates: Requirements 1.1, 1.2, 1.3, 2.1, 2.2, 2.3**
 *
 * This test surfaces counterexamples that demonstrate the bug exists by attempting to render
 * GTM components that use private $gtmService properties, which are not accessible in Blade templates.
 */
class GTMComponentVariableBugConditionTest extends TestCase
{
    /**
     * Test that GTMHead component can render and access $gtmService->isEnabled()
     *
     * **EXPECTED OUTCOME ON UNFIXED CODE**: Test FAILS with "Undefined variable $gtmService" error
     * **EXPECTED OUTCOME ON FIXED CODE**: Test PASSES - component renders successfully
     *
     * **Validates: Requirements 1.1, 2.1, 2.3**
     */
    public function test_gtm_head_component_renders_without_undefined_variable_errors(): void
    {
        // Arrange: Enable GTM for testing
        config(['analytics.gtm.enabled' => true]);
        config(['analytics.gtm.container_id' => 'GTM-TEST123']);
        config(['app.env' => 'production']);

        $gtmService = app(GTMService::class);
        $component = new GTMHead($gtmService);

        // Act: Attempt to render the component
        // On unfixed code, this will throw "Undefined variable $gtmService" error
        // because the private property is not accessible in the Blade template
        $rendered = $component->render()->render();

        // Assert: Component should render successfully with $gtmService accessible
        $this->assertStringContainsString('googletagmanager.com', $rendered);
        $this->assertStringContainsString('GTM-TEST123', $rendered);
        $this->assertStringContainsString('<script', $rendered);
        $this->assertStringContainsString('Google Tag Manager', $rendered);
    }

    /**
     * Test that GTMBody component can render and access $gtmService->isEnabled()
     *
     * **EXPECTED OUTCOME ON UNFIXED CODE**: Test FAILS with "Undefined variable $gtmService" error
     * **EXPECTED OUTCOME ON FIXED CODE**: Test PASSES - component renders successfully
     *
     * **Validates: Requirements 1.2, 2.2, 2.3**
     */
    public function test_gtm_body_component_renders_without_undefined_variable_errors(): void
    {
        // Arrange: Enable GTM for testing
        config(['analytics.gtm.enabled' => true]);
        config(['analytics.gtm.container_id' => 'GTM-TEST123']);
        config(['app.env' => 'production']);

        $gtmService = app(GTMService::class);
        $component = new GTMBody($gtmService);

        // Act: Attempt to render the component
        // On unfixed code, this will throw "Undefined variable $gtmService" error
        // because the private property is not accessible in the Blade template
        $rendered = $component->render()->render();

        // Assert: Component should render successfully with $gtmService accessible
        $this->assertStringContainsString('<noscript>', $rendered);
        $this->assertStringContainsString('<iframe', $rendered);
        $this->assertStringContainsString('GTM-TEST123', $rendered);
        $this->assertStringContainsString('googletagmanager.com/ns.html', $rendered);
    }

    /**
     * Test that GTMHead with valid container ID outputs head script without template errors
     *
     * **EXPECTED OUTCOME ON UNFIXED CODE**: Test FAILS with "Undefined variable $gtmService" error
     * **EXPECTED OUTCOME ON FIXED CODE**: Test PASSES - head script is output correctly
     *
     * **Validates: Requirements 1.1, 2.1, 2.3**
     */
    public function test_gtm_head_outputs_script_with_valid_container_id(): void
    {
        // Arrange: Enable GTM with valid container ID
        config(['analytics.gtm.enabled' => true]);
        config(['analytics.gtm.container_id' => 'GTM-PROD456']);
        config(['app.env' => 'production']);

        $gtmService = app(GTMService::class);
        $component = new GTMHead($gtmService);

        // Act: Render the component
        $rendered = $component->render()->render();

        // Assert: Verify the GTM head script is properly output
        $this->assertStringContainsString('GTM-PROD456', $rendered);
        $this->assertStringContainsString("w[l].push({'gtm.start':", $rendered);
        $this->assertStringContainsString('www.googletagmanager.com/gtm.js', $rendered);

        // Verify it's a complete script tag
        $this->assertMatchesRegularExpression('/<script[^>]*>.*<\/script>/s', $rendered);
    }

    /**
     * Test that GTMBody with valid container ID outputs body noscript without template errors
     *
     * **EXPECTED OUTCOME ON UNFIXED CODE**: Test FAILS with "Undefined variable $gtmService" error
     * **EXPECTED OUTCOME ON FIXED CODE**: Test PASSES - body noscript is output correctly
     *
     * **Validates: Requirements 1.2, 2.2, 2.3**
     */
    public function test_gtm_body_outputs_noscript_with_valid_container_id(): void
    {
        // Arrange: Enable GTM with valid container ID
        config(['analytics.gtm.enabled' => true]);
        config(['analytics.gtm.container_id' => 'GTM-PROD789']);
        config(['app.env' => 'production']);

        $gtmService = app(GTMService::class);
        $component = new GTMBody($gtmService);

        // Act: Render the component
        $rendered = $component->render()->render();

        // Assert: Verify the GTM body noscript is properly output
        $this->assertStringContainsString('GTM-PROD789', $rendered);
        $this->assertStringContainsString('www.googletagmanager.com/ns.html?id=GTM-PROD789', $rendered);

        // Verify it's a complete noscript with iframe
        $this->assertMatchesRegularExpression('/<noscript>.*<iframe.*<\/noscript>/s', $rendered);
        $this->assertStringContainsString('height="0"', $rendered);
        $this->assertStringContainsString('width="0"', $rendered);
    }

    /**
     * Test that GTMHead renders empty output when GTM is disabled
     *
     * This test verifies that even when GTM is disabled, the component can still render
     * without throwing "Undefined variable" errors.
     *
     * **EXPECTED OUTCOME ON UNFIXED CODE**: Test FAILS with "Undefined variable $gtmService" error
     * **EXPECTED OUTCOME ON FIXED CODE**: Test PASSES - component renders empty output
     *
     * **Validates: Requirements 1.1, 2.1**
     */
    public function test_gtm_head_renders_empty_when_disabled(): void
    {
        // Arrange: Disable GTM
        config(['analytics.gtm.enabled' => false]);
        config(['app.env' => 'production']);

        $gtmService = app(GTMService::class);
        $component = new GTMHead($gtmService);

        // Act: Render the component
        $rendered = $component->render()->render();

        // Assert: Component should render successfully with empty output
        $this->assertEmpty(trim($rendered));
    }

    /**
     * Test that GTMBody renders empty output when GTM is disabled
     *
     * This test verifies that even when GTM is disabled, the component can still render
     * without throwing "Undefined variable" errors.
     *
     * **EXPECTED OUTCOME ON UNFIXED CODE**: Test FAILS with "Undefined variable $gtmService" error
     * **EXPECTED OUTCOME ON FIXED CODE**: Test PASSES - component renders empty output
     *
     * **Validates: Requirements 1.2, 2.2**
     */
    public function test_gtm_body_renders_empty_when_disabled(): void
    {
        // Arrange: Disable GTM
        config(['analytics.gtm.enabled' => false]);
        config(['app.env' => 'production']);

        $gtmService = app(GTMService::class);
        $component = new GTMBody($gtmService);

        // Act: Render the component
        $rendered = $component->render()->render();

        // Assert: Component should render successfully with empty output
        $this->assertEmpty(trim($rendered));
    }

    /**
     * Test that GTMHead renders empty in local environment
     *
     * **EXPECTED OUTCOME ON UNFIXED CODE**: Test FAILS with "Undefined variable $gtmService" error
     * **EXPECTED OUTCOME ON FIXED CODE**: Test PASSES - component renders empty output
     *
     * **Validates: Requirements 1.1, 2.1**
     */
    public function test_gtm_head_renders_empty_in_local_environment(): void
    {
        // Arrange: Set local environment (GTM should be disabled)
        config(['analytics.gtm.enabled' => true]);
        config(['analytics.gtm.container_id' => 'GTM-TEST123']);
        config(['app.env' => 'local']);

        $gtmService = app(GTMService::class);
        $component = new GTMHead($gtmService);

        // Act: Render the component
        $rendered = $component->render()->render();

        // Assert: Component should render successfully with empty output (GTM disabled in local)
        $this->assertEmpty(trim($rendered));
    }

    /**
     * Test that GTMBody renders empty in local environment
     *
     * **EXPECTED OUTCOME ON UNFIXED CODE**: Test FAILS with "Undefined variable $gtmService" error
     * **EXPECTED OUTCOME ON FIXED CODE**: Test PASSES - component renders empty output
     *
     * **Validates: Requirements 1.2, 2.2**
     */
    public function test_gtm_body_renders_empty_in_local_environment(): void
    {
        // Arrange: Set local environment (GTM should be disabled)
        config(['analytics.gtm.enabled' => true]);
        config(['analytics.gtm.container_id' => 'GTM-TEST123']);
        config(['app.env' => 'local']);

        $gtmService = app(GTMService::class);
        $component = new GTMBody($gtmService);

        // Act: Render the component
        $rendered = $component->render()->render();

        // Assert: Component should render successfully with empty output (GTM disabled in local)
        $this->assertEmpty(trim($rendered));
    }
}
