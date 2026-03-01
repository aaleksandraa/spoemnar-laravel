<?php

namespace Tests\Unit\Services\SEO;

use App\Models\Memorial;
use App\Models\Photo;
use App\Services\SEO\StructuredDataService;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class StructuredDataServiceTest extends TestCase
{
    private StructuredDataService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new StructuredDataService();
    }

    public function test_generate_organization_schema_returns_valid_schema(): void
    {
        Config::set('seo.structured_data.organization', [
            'name' => 'Spomenar',
            'logo' => '/logo.png',
            'contact_email' => 'info@example.com',
        ]);
        Config::set('seo.site.name', 'Spomenar');
        Config::set('seo.site.url', 'https://example.com');
        Config::set('seo.social', [
            'facebook' => 'https://facebook.com/spomenar',
            'twitter' => 'https://twitter.com/spomenar',
        ]);

        $schema = $this->service->generateOrganizationSchema();

        $this->assertIsArray($schema);
        $this->assertEquals('https://schema.org', $schema['@context']);
        $this->assertEquals('Organization', $schema['@type']);
        $this->assertEquals('Spomenar', $schema['name']);
        $this->assertEquals('https://example.com', $schema['url']);
        $this->assertEquals('https://example.com/logo.png', $schema['logo']);
        $this->assertArrayHasKey('sameAs', $schema);
        $this->assertContains('https://facebook.com/spomenar', $schema['sameAs']);
        $this->assertContains('https://twitter.com/spomenar', $schema['sameAs']);
        $this->assertArrayHasKey('contactPoint', $schema);
        $this->assertEquals('ContactPoint', $schema['contactPoint']['@type']);
        $this->assertEquals('customer service', $schema['contactPoint']['contactType']);
        $this->assertEquals('info@example.com', $schema['contactPoint']['email']);
    }

    public function test_generate_organization_schema_without_logo(): void
    {
        Config::set('seo.structured_data.organization', [
            'name' => 'Spomenar',
            'contact_email' => 'info@example.com',
        ]);
        Config::set('seo.site.url', 'https://example.com');

        $schema = $this->service->generateOrganizationSchema();

        $this->assertArrayNotHasKey('logo', $schema);
    }

    public function test_generate_organization_schema_without_social_media(): void
    {
        Config::set('seo.structured_data.organization', [
            'name' => 'Spomenar',
        ]);
        Config::set('seo.site.url', 'https://example.com');
        Config::set('seo.social', []);

        $schema = $this->service->generateOrganizationSchema();

        $this->assertArrayNotHasKey('sameAs', $schema);
    }

    public function test_generate_organization_schema_without_contact_email(): void
    {
        Config::set('seo.structured_data.organization', [
            'name' => 'Spomenar',
        ]);
        Config::set('seo.site.url', 'https://example.com');

        $schema = $this->service->generateOrganizationSchema();

        $this->assertArrayNotHasKey('contactPoint', $schema);
    }

    public function test_generate_website_schema_includes_search_action(): void
    {
        Config::set('seo.site.name', 'Spomenar');
        Config::set('seo.site.url', 'https://example.com');

        $schema = $this->service->generateWebSiteSchema();

        $this->assertIsArray($schema);
        $this->assertEquals('https://schema.org', $schema['@context']);
        $this->assertEquals('WebSite', $schema['@type']);
        $this->assertEquals('Spomenar', $schema['name']);
        $this->assertEquals('https://example.com', $schema['url']);
        $this->assertArrayHasKey('potentialAction', $schema);
        $this->assertEquals('SearchAction', $schema['potentialAction']['@type']);
        $this->assertArrayHasKey('target', $schema['potentialAction']);
        $this->assertEquals('EntryPoint', $schema['potentialAction']['target']['@type']);
        $this->assertEquals('https://example.com/search?q={search_term_string}', $schema['potentialAction']['target']['urlTemplate']);
        $this->assertEquals('required name=search_term_string', $schema['potentialAction']['query-input']);
    }

    public function test_generate_person_schema_includes_all_person_properties(): void
    {
        $memorial = new Memorial();
        $memorial->first_name = 'John';
        $memorial->last_name = 'Doe';
        $memorial->birth_date = now()->subYears(70)->startOfDay();
        $memorial->death_date = now()->subYear()->startOfDay();
        $memorial->biography = '<p>Loving father and dedicated teacher who touched many lives.</p>';

        // Create a mock object for primaryPhoto with url property
        $photo = new \stdClass();
        $photo->url = 'https://example.com/photos/john-doe.jpg';
        $memorial->setRelation('primaryPhoto', $photo);

        $schema = $this->service->generatePersonSchema($memorial);

        $this->assertIsArray($schema);
        $this->assertEquals('https://schema.org', $schema['@context']);
        $this->assertEquals('Person', $schema['@type']);
        $this->assertEquals('John Doe', $schema['name']);
        $this->assertEquals($memorial->birth_date->format('Y-m-d'), $schema['birthDate']);
        $this->assertEquals($memorial->death_date->format('Y-m-d'), $schema['deathDate']);
        $this->assertEquals('https://example.com/photos/john-doe.jpg', $schema['image']);
        $this->assertArrayHasKey('description', $schema);
        $this->assertStringNotContainsString('<p>', $schema['description']);
        $this->assertStringNotContainsString('</p>', $schema['description']);
    }

    public function test_generate_person_schema_without_optional_fields(): void
    {
        $memorial = new Memorial();
        $memorial->first_name = 'Jane';
        $memorial->last_name = 'Smith';

        $schema = $this->service->generatePersonSchema($memorial);

        $this->assertIsArray($schema);
        $this->assertEquals('Jane Smith', $schema['name']);
        $this->assertArrayNotHasKey('birthDate', $schema);
        $this->assertArrayNotHasKey('deathDate', $schema);
        $this->assertArrayNotHasKey('image', $schema);
        $this->assertArrayNotHasKey('description', $schema);
    }

    public function test_generate_person_schema_truncates_long_description(): void
    {
        $memorial = new Memorial();
        $memorial->first_name = 'John';
        $memorial->last_name = 'Doe';
        $memorial->biography = str_repeat('A very long biography text. ', 100);

        $schema = $this->service->generatePersonSchema($memorial);

        $this->assertArrayHasKey('description', $schema);
        $this->assertLessThanOrEqual(500, mb_strlen($schema['description']));
    }

    public function test_generate_breadcrumb_schema_includes_all_breadcrumb_items(): void
    {
        $breadcrumbs = [
            ['name' => 'Home', 'url' => 'https://example.com'],
            ['name' => 'Memorials', 'url' => 'https://example.com/memorials'],
            ['name' => 'John Doe', 'url' => 'https://example.com/memorials/john-doe'],
        ];

        $schema = $this->service->generateBreadcrumbSchema($breadcrumbs);

        $this->assertIsArray($schema);
        $this->assertEquals('https://schema.org', $schema['@context']);
        $this->assertEquals('BreadcrumbList', $schema['@type']);
        $this->assertArrayHasKey('itemListElement', $schema);
        $this->assertCount(3, $schema['itemListElement']);

        $this->assertEquals('ListItem', $schema['itemListElement'][0]['@type']);
        $this->assertEquals(1, $schema['itemListElement'][0]['position']);
        $this->assertEquals('Home', $schema['itemListElement'][0]['name']);
        $this->assertEquals('https://example.com', $schema['itemListElement'][0]['item']);

        $this->assertEquals('ListItem', $schema['itemListElement'][1]['@type']);
        $this->assertEquals(2, $schema['itemListElement'][1]['position']);
        $this->assertEquals('Memorials', $schema['itemListElement'][1]['name']);
        $this->assertEquals('https://example.com/memorials', $schema['itemListElement'][1]['item']);

        $this->assertEquals('ListItem', $schema['itemListElement'][2]['@type']);
        $this->assertEquals(3, $schema['itemListElement'][2]['position']);
        $this->assertEquals('John Doe', $schema['itemListElement'][2]['name']);
        $this->assertEquals('https://example.com/memorials/john-doe', $schema['itemListElement'][2]['item']);
    }

    public function test_generate_breadcrumb_schema_with_empty_array(): void
    {
        $schema = $this->service->generateBreadcrumbSchema([]);

        $this->assertIsArray($schema);
        $this->assertEquals('BreadcrumbList', $schema['@type']);
        $this->assertArrayHasKey('itemListElement', $schema);
        $this->assertCount(0, $schema['itemListElement']);
    }

    public function test_to_json_ld_converts_array_to_valid_json_ld(): void
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => 'Test Organization',
            'url' => 'https://example.com',
        ];

        $jsonLd = $this->service->toJsonLd($schema);

        $this->assertIsString($jsonLd);
        $this->assertJson($jsonLd);

        $decoded = json_decode($jsonLd, true);
        $this->assertEquals($schema, $decoded);
    }

    public function test_to_json_ld_preserves_unicode_characters(): void
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Person',
            'name' => 'Đorđe Petrović',
        ];

        $jsonLd = $this->service->toJsonLd($schema);

        $this->assertStringContainsString('Đorđe Petrović', $jsonLd);
        $this->assertStringNotContainsString('\u', $jsonLd);
    }

    public function test_to_json_ld_does_not_escape_slashes(): void
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'url' => 'https://example.com/path',
        ];

        $jsonLd = $this->service->toJsonLd($schema);

        $this->assertStringContainsString('https://example.com/path', $jsonLd);
        $this->assertStringNotContainsString('https:\/\/', $jsonLd);
    }

    public function test_validate_schema_accepts_valid_organization_schema(): void
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => 'Test Org',
            'url' => 'https://example.com',
        ];

        $this->assertTrue($this->service->validateSchema($schema));
    }

    public function test_validate_schema_accepts_valid_website_schema(): void
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => 'Test Site',
            'url' => 'https://example.com',
        ];

        $this->assertTrue($this->service->validateSchema($schema));
    }

    public function test_validate_schema_accepts_valid_person_schema(): void
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Person',
            'name' => 'John Doe',
        ];

        $this->assertTrue($this->service->validateSchema($schema));
    }

    public function test_validate_schema_accepts_valid_breadcrumb_schema(): void
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => 'Home',
                    'item' => 'https://example.com',
                ],
            ],
        ];

        $this->assertTrue($this->service->validateSchema($schema));
    }

    public function test_validate_schema_rejects_schema_without_context(): void
    {
        $schema = [
            '@type' => 'Organization',
            'name' => 'Test Org',
        ];

        $this->assertFalse($this->service->validateSchema($schema));
    }

    public function test_validate_schema_rejects_schema_without_type(): void
    {
        $schema = [
            '@context' => 'https://schema.org',
            'name' => 'Test Org',
        ];

        $this->assertFalse($this->service->validateSchema($schema));
    }

    public function test_validate_schema_rejects_organization_without_name(): void
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'url' => 'https://example.com',
        ];

        $this->assertFalse($this->service->validateSchema($schema));
    }

    public function test_validate_schema_rejects_organization_without_url(): void
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => 'Test Org',
        ];

        $this->assertFalse($this->service->validateSchema($schema));
    }

    public function test_validate_schema_rejects_person_without_name(): void
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Person',
        ];

        $this->assertFalse($this->service->validateSchema($schema));
    }

    public function test_validate_schema_rejects_breadcrumb_without_items(): void
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
        ];

        $this->assertFalse($this->service->validateSchema($schema));
    }
}
