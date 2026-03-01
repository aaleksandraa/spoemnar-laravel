<?php

namespace Tests\Feature\SEO;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\View\Components\SEO\StructuredData;
use App\Models\Memorial;
use App\Models\User;

/**
 * SEO Structured Data Preservation Property Tests
 *
 * **IMPORTANT**: These tests follow observation-first methodology
 * **GOAL**: Capture baseline behavior by calling getJsonLd() method directly on component instance
 * **EXPECTED OUTCOME**: Tests define the expected JSON-LD output that must be preserved
 *
 * **Validates: Property 2 (Preservation) - JSON-LD Output Unchanged**
 * **Validates: Requirements 3.1, 3.2, 3.3, 3.4**
 *
 * Since the unfixed code crashes when rendering the template, we observe expected behavior
 * by calling the getJsonLd() method directly on the component instance. This establishes
 * the baseline JSON-LD output that must be preserved after the fix.
 */
class StructuredDataPreservationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property: Organization schema JSON-LD output format is preserved
     *
     * This property-based test verifies that the organization schema generates
     * the expected JSON-LD output across various configurations.
     *
     * **Validates: Requirements 3.1, 3.2, 3.3, 3.4**
     */
    public function test_organization_schema_json_ld_output_is_preserved(): void
    {
        // Property: For all valid organization configurations, getJsonLd() produces expected JSON-LD
        $testCases = [
            [
                'name' => 'Spomenar',
                'url' => 'https://example.com',
                'logo' => '/images/logo.png',
            ],
            [
                'name' => 'Test Organization',
                'url' => 'https://test.com',
                'logo' => '/images/test-logo.png',
            ],
            [
                'name' => 'Another Org',
                'url' => 'https://another.org',
                'logo' => '/logo.svg',
            ],
        ];

        foreach ($testCases as $testCase) {
            // Arrange: Configure organization schema data
            config([
                'seo.structured_data.organization.name' => $testCase['name'],
                'seo.site.url' => $testCase['url'],
                'seo.structured_data.organization.logo' => $testCase['logo'],
            ]);

            // Act: Call getJsonLd() directly on component instance
            $component = new StructuredData(type: 'organization');
            $jsonLd = $component->getJsonLd();

            // Assert: Verify expected JSON-LD output format is preserved
            $this->assertNotNull($jsonLd, "Organization schema should generate JSON-LD output");
            $this->assertStringContainsString('@context', $jsonLd);
            $this->assertStringContainsString('https://schema.org', $jsonLd);
            $this->assertStringContainsString('Organization', $jsonLd);
            $this->assertStringContainsString($testCase['name'], $jsonLd);
            $this->assertStringContainsString($testCase['url'], $jsonLd);
            $this->assertStringContainsString($testCase['logo'], $jsonLd);
        }
    }

    /**
     * Property: Website schema JSON-LD output format is preserved
     *
     * This property-based test verifies that the website schema generates
     * the expected JSON-LD output across various configurations.
     *
     * **Validates: Requirements 3.1, 3.2, 3.3, 3.4**
     */
    public function test_website_schema_json_ld_output_is_preserved(): void
    {
        // Property: For all valid website configurations, getJsonLd() produces expected JSON-LD
        $testCases = [
            [
                'name' => 'Spomenar',
                'url' => 'https://example.com',
            ],
            [
                'name' => 'Test Site',
                'url' => 'https://test.com',
            ],
            [
                'name' => 'Another Website',
                'url' => 'https://another.org',
            ],
        ];

        foreach ($testCases as $testCase) {
            // Arrange: Configure website schema data
            config([
                'seo.site.name' => $testCase['name'],
                'seo.site.url' => $testCase['url'],
            ]);

            // Act: Call getJsonLd() directly on component instance
            $component = new StructuredData(type: 'website');
            $jsonLd = $component->getJsonLd();

            // Assert: Verify expected JSON-LD output format is preserved
            $this->assertNotNull($jsonLd, "Website schema should generate JSON-LD output");
            $this->assertStringContainsString('@context', $jsonLd);
            $this->assertStringContainsString('https://schema.org', $jsonLd);
            $this->assertStringContainsString('WebSite', $jsonLd);
            $this->assertStringContainsString($testCase['name'], $jsonLd);
            $this->assertStringContainsString($testCase['url'], $jsonLd);
            $this->assertStringContainsString('SearchAction', $jsonLd);
            $this->assertStringContainsString('potentialAction', $jsonLd);
        }
    }

    /**
     * Property: Person schema JSON-LD output format with Memorial data is preserved
     *
     * This property-based test verifies that the person schema generates
     * the expected JSON-LD output across various Memorial configurations.
     *
     * **Validates: Requirements 3.1, 3.2, 3.3, 3.4**
     */
    public function test_person_schema_json_ld_output_with_memorial_data_is_preserved(): void
    {
        // Property: For all valid Memorial data, getJsonLd() produces expected person schema JSON-LD
        $testCases = [
            [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'birth_date' => '1950-01-15',
                'death_date' => '2023-12-20',
                'biography' => 'A beloved family member.',
            ],
            [
                'first_name' => 'Jane',
                'last_name' => 'Smith',
                'birth_date' => '1945-05-10',
                'death_date' => '2022-08-15',
                'biography' => 'A wonderful person.',
            ],
            [
                'first_name' => 'Bob',
                'last_name' => 'Johnson',
                'birth_date' => '1960-03-25',
                'death_date' => '2024-01-05',
                'biography' => 'Always remembered.',
            ],
        ];

        foreach ($testCases as $testCase) {
            // Arrange: Create a Memorial model with test data
            $user = User::factory()->create();
            $memorial = Memorial::factory()->create([
                'user_id' => $user->id,
                'first_name' => $testCase['first_name'],
                'last_name' => $testCase['last_name'],
                'birth_date' => $testCase['birth_date'],
                'death_date' => $testCase['death_date'],
                'biography' => $testCase['biography'],
            ]);

            // Act: Call getJsonLd() directly on component instance with Memorial data
            $component = new StructuredData(type: 'person', data: $memorial);
            $jsonLd = $component->getJsonLd();

            // Assert: Verify expected JSON-LD output format is preserved
            $this->assertNotNull($jsonLd, "Person schema should generate JSON-LD output for Memorial");
            $this->assertStringContainsString('@context', $jsonLd);
            $this->assertStringContainsString('https://schema.org', $jsonLd);
            $this->assertStringContainsString('Person', $jsonLd);
            $this->assertStringContainsString($testCase['first_name'] . ' ' . $testCase['last_name'], $jsonLd);
            $this->assertStringContainsString($testCase['birth_date'], $jsonLd);
            $this->assertStringContainsString($testCase['death_date'], $jsonLd);
        }
    }

    /**
     * Property: Breadcrumb schema JSON-LD output format is preserved
     *
     * This property-based test verifies that the breadcrumb schema generates
     * the expected JSON-LD output across various breadcrumb configurations.
     *
     * **Validates: Requirements 3.1, 3.2, 3.3, 3.4**
     */
    public function test_breadcrumb_schema_json_ld_output_is_preserved(): void
    {
        // Property: For all valid breadcrumb arrays, getJsonLd() produces expected JSON-LD
        $testCases = [
            [
                ['name' => 'Home', 'url' => 'https://example.com'],
                ['name' => 'Memorials', 'url' => 'https://example.com/memorials'],
                ['name' => 'John Doe', 'url' => 'https://example.com/memorials/john-doe'],
            ],
            [
                ['name' => 'Home', 'url' => 'https://test.com'],
                ['name' => 'About', 'url' => 'https://test.com/about'],
            ],
            [
                ['name' => 'Home', 'url' => 'https://another.org'],
                ['name' => 'Category', 'url' => 'https://another.org/category'],
                ['name' => 'Subcategory', 'url' => 'https://another.org/category/sub'],
                ['name' => 'Item', 'url' => 'https://another.org/category/sub/item'],
            ],
        ];

        foreach ($testCases as $breadcrumbs) {
            // Act: Call getJsonLd() directly on component instance with breadcrumb data
            $component = new StructuredData(type: 'breadcrumb', breadcrumbs: $breadcrumbs);
            $jsonLd = $component->getJsonLd();

            // Assert: Verify expected JSON-LD output format is preserved
            $this->assertNotNull($jsonLd, "Breadcrumb schema should generate JSON-LD output");
            $this->assertStringContainsString('@context', $jsonLd);
            $this->assertStringContainsString('https://schema.org', $jsonLd);
            $this->assertStringContainsString('BreadcrumbList', $jsonLd);
            $this->assertStringContainsString('itemListElement', $jsonLd);

            // Verify all breadcrumb items are present
            foreach ($breadcrumbs as $breadcrumb) {
                $this->assertStringContainsString($breadcrumb['name'], $jsonLd);
            }
        }
    }

    /**
     * Property: Null output when invalid schema type is provided
     *
     * This property-based test verifies that the component returns null
     * when an invalid schema type is provided, preserving the conditional rendering behavior.
     *
     * **Validates: Requirements 3.1, 3.4**
     */
    public function test_null_output_when_invalid_schema_type_is_preserved(): void
    {
        // Property: For all invalid schema types, getJsonLd() returns null
        $invalidTypes = [
            'invalid',
            'unknown',
            'article',
            'product',
            '',
        ];

        foreach ($invalidTypes as $invalidType) {
            // Act: Call getJsonLd() directly on component instance with invalid type
            $component = new StructuredData(type: $invalidType);
            $jsonLd = $component->getJsonLd();

            // Assert: Verify null is returned for invalid schema types
            $this->assertNull($jsonLd, "Invalid schema type '{$invalidType}' should return null");
        }
    }

    /**
     * Property: Null output when person schema has no data
     *
     * This property-based test verifies that the person schema returns null
     * when no Memorial data is provided, preserving the conditional rendering behavior.
     *
     * **Validates: Requirements 3.1, 3.4**
     */
    public function test_null_output_when_person_schema_has_no_data_is_preserved(): void
    {
        // Act: Call getJsonLd() directly on component instance with person type but no data
        $component = new StructuredData(type: 'person', data: null);
        $jsonLd = $component->getJsonLd();

        // Assert: Verify null is returned when person schema has no data
        $this->assertNull($jsonLd, "Person schema without data should return null");
    }

    /**
     * Property: Null output when breadcrumb schema has no breadcrumbs
     *
     * This property-based test verifies that the breadcrumb schema returns null
     * when no breadcrumb array is provided, preserving the conditional rendering behavior.
     *
     * **Validates: Requirements 3.1, 3.4**
     */
    public function test_null_output_when_breadcrumb_schema_has_no_breadcrumbs_is_preserved(): void
    {
        // Act: Call getJsonLd() directly on component instance with breadcrumb type but no breadcrumbs
        $component = new StructuredData(type: 'breadcrumb', breadcrumbs: null);
        $jsonLd = $component->getJsonLd();

        // Assert: Verify null is returned when breadcrumb schema has no breadcrumbs
        $this->assertNull($jsonLd, "Breadcrumb schema without breadcrumbs should return null");
    }

    /**
     * Property: Empty breadcrumb array returns null
     *
     * This property-based test verifies that the breadcrumb schema returns null
     * when an empty breadcrumb array is provided.
     *
     * **Validates: Requirements 3.1, 3.4**
     */
    public function test_null_output_when_breadcrumb_array_is_empty_is_preserved(): void
    {
        // Act: Call getJsonLd() directly on component instance with empty breadcrumb array
        $component = new StructuredData(type: 'breadcrumb', breadcrumbs: []);
        $jsonLd = $component->getJsonLd();

        // Assert: Verify null is returned for empty breadcrumb array
        $this->assertNull($jsonLd, "Breadcrumb schema with empty array should return null");
    }
}
