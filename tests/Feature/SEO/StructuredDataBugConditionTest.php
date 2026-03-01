<?php

namespace Tests\Feature\SEO;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;
use App\Models\Memorial;
use App\Models\User;

/**
 * SEO Structured Data Bug Condition Exploration Test
 *
 * **CRITICAL**: This test MUST FAIL on unfixed code - failure confirms the bug exists
 * **DO NOT attempt to fix the test or the code when it fails**
 * **NOTE**: This test encodes the expected behavior - it will validate the fix when it passes after implementation
 *
 * **Validates: Property 1 (Fault Condition) - Component Renders Without $this Error**
 * **Validates: Requirements 2.1, 2.2, 2.3**
 *
 * This test surfaces counterexamples that demonstrate the bug exists by attempting to render
 * the SEO structured data component that incorrectly uses $this->getJsonLd() in the template.
 *
 * **EXPECTED COUNTEREXAMPLES**:
 * - Fatal error: "Using $this when not in object context" at line 2 of structured-data.blade.php
 * - 500 Server Error when attempting to render any page using the component
 * - Possible cause: Incorrect Blade component template syntax using $this->
 */
class StructuredDataBugConditionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that organization schema component renders without $this context errors
     *
     * **EXPECTED OUTCOME ON UNFIXED CODE**: Test FAILS with "Using $this when not in object context" error
     * **EXPECTED OUTCOME ON FIXED CODE**: Test PASSES - component renders successfully with organization schema
     *
     * **Validates: Requirements 2.1, 2.2, 2.3**
     */
    public function test_organization_schema_component_renders_without_this_context_errors(): void
    {
        // Arrange: Configure organization schema data
        config([
            'seo.structured_data.organization.name' => 'Spomenar',
            'seo.site.url' => 'https://example.com',
            'seo.structured_data.organization.logo' => '/images/logo.png',
        ]);

        // Act: Attempt to render the component with organization type
        // On unfixed code, this will throw "Using $this when not in object context" fatal error
        $rendered = View::make('components.seo.structured-data', [
            'type' => 'organization',
            'data' => null,
            'breadcrumbs' => null,
        ])->render();

        // Assert: Component should render successfully with JSON-LD script tag
        $this->assertStringContainsString('<script type="application/ld+json">', $rendered);
        $this->assertStringContainsString('"@type": "Organization"', $rendered);
        $this->assertStringContainsString('"name": "Spomenar"', $rendered);
    }

    /**
     * Test that website schema component renders without $this context errors
     *
     * **EXPECTED OUTCOME ON UNFIXED CODE**: Test FAILS with "Using $this when not in object context" error
     * **EXPECTED OUTCOME ON FIXED CODE**: Test PASSES - component renders successfully with website schema
     *
     * **Validates: Requirements 2.1, 2.2, 2.3**
     */
    public function test_website_schema_component_renders_without_this_context_errors(): void
    {
        // Arrange: Configure website schema data
        config([
            'seo.site.name' => 'Spomenar',
            'seo.site.url' => 'https://example.com',
        ]);

        // Act: Attempt to render the component with website type
        // On unfixed code, this will throw "Using $this when not in object context" fatal error
        $rendered = View::make('components.seo.structured-data', [
            'type' => 'website',
            'data' => null,
            'breadcrumbs' => null,
        ])->render();

        // Assert: Component should render successfully with JSON-LD script tag
        $this->assertStringContainsString('<script type="application/ld+json">', $rendered);
        $this->assertStringContainsString('"@type": "WebSite"', $rendered);
        $this->assertStringContainsString('"name": "Spomenar"', $rendered);
        $this->assertStringContainsString('SearchAction', $rendered);
    }

    /**
     * Test that person schema component renders without $this context errors
     *
     * **EXPECTED OUTCOME ON UNFIXED CODE**: Test FAILS with "Using $this when not in object context" error
     * **EXPECTED OUTCOME ON FIXED CODE**: Test PASSES - component renders successfully with person schema
     *
     * **Validates: Requirements 2.1, 2.2, 2.3**
     */
    public function test_person_schema_component_renders_without_this_context_errors(): void
    {
        // Arrange: Create a mock Memorial model for person schema
        $user = User::factory()->create();
        $memorial = Memorial::factory()->create([
            'user_id' => $user->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'birth_date' => '1950-01-15',
            'death_date' => '2023-12-20',
            'biography' => 'A beloved family member.',
        ]);

        // Act: Attempt to render the component with person type and memorial data
        // On unfixed code, this will throw "Using $this when not in object context" fatal error
        $rendered = View::make('components.seo.structured-data', [
            'type' => 'person',
            'data' => $memorial,
            'breadcrumbs' => null,
        ])->render();

        // Assert: Component should render successfully with JSON-LD script tag
        $this->assertStringContainsString('<script type="application/ld+json">', $rendered);
        $this->assertStringContainsString('"@type": "Person"', $rendered);
        $this->assertStringContainsString('"name": "John Doe"', $rendered);
        $this->assertStringContainsString('"birthDate": "1950-01-15"', $rendered);
        $this->assertStringContainsString('"deathDate": "2023-12-20"', $rendered);
    }

    /**
     * Test that breadcrumb schema component renders without $this context errors
     *
     * **EXPECTED OUTCOME ON UNFIXED CODE**: Test FAILS with "Using $this when not in object context" error
     * **EXPECTED OUTCOME ON FIXED CODE**: Test PASSES - component renders successfully with breadcrumb schema
     *
     * **Validates: Requirements 2.1, 2.2, 2.3**
     */
    public function test_breadcrumb_schema_component_renders_without_this_context_errors(): void
    {
        // Arrange: Create breadcrumb data
        $breadcrumbs = [
            ['name' => 'Home', 'url' => 'https://example.com'],
            ['name' => 'Memorials', 'url' => 'https://example.com/memorials'],
            ['name' => 'John Doe', 'url' => 'https://example.com/memorials/john-doe'],
        ];

        // Act: Attempt to render the component with breadcrumb type
        // On unfixed code, this will throw "Using $this when not in object context" fatal error
        $rendered = View::make('components.seo.structured-data', [
            'type' => 'breadcrumb',
            'data' => null,
            'breadcrumbs' => $breadcrumbs,
        ])->render();

        // Assert: Component should render successfully with JSON-LD script tag
        $this->assertStringContainsString('<script type="application/ld+json">', $rendered);
        $this->assertStringContainsString('"@type": "BreadcrumbList"', $rendered);
        $this->assertStringContainsString('"name": "Home"', $rendered);
        $this->assertStringContainsString('"name": "Memorials"', $rendered);
        $this->assertStringContainsString('"name": "John Doe"', $rendered);
    }

    /**
     * Test that the template file uses correct syntax (not $this)
     *
     * This test verifies that the template contains the fixed code by reading the file directly.
     *
     * **EXPECTED OUTCOME ON UNFIXED CODE**: Test FAILS - template contains $this->getJsonLd()
     * **EXPECTED OUTCOME ON FIXED CODE**: Test PASSES - template should NOT use $this->
     *
     * **Validates: Requirements 2.2**
     */
    public function test_template_contains_correct_syntax(): void
    {
        // Arrange: Read the template file
        $templatePath = resource_path('views/components/seo/structured-data.blade.php');
        $templateContent = file_get_contents($templatePath);

        // Assert: Template should NOT contain the buggy $this->getJsonLd() syntax on fixed code
        $this->assertStringNotContainsString('$this->getJsonLd()', $templateContent);

        // Assert: Template should contain the service instantiation and method calls
        $this->assertStringContainsString('StructuredDataService', $templateContent);
        $this->assertStringContainsString('$service', $templateContent);
    }
}
