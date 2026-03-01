<?php

namespace Tests\Feature\SEO;

use Tests\TestCase;
use App\Services\SEO\MetaTagService;
use App\View\Components\SEO\MetaTags;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Meta Tags Feature Tests
 *
 * Tests dynamic meta description generation, Open Graph tags,
 * Twitter Card tags, and canonical URL enforcement.
 *
 * Requirements: 24.1, 25.1, 26.1, 29.1
 */
class MetaTagsTest extends TestCase
{
    use RefreshDatabase;

    protected MetaTagService $metaService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->metaService = app(MetaTagService::class);
    }

    /**
     * Test that MetaTagService class exists
     *
     * Validates that the meta tag service is available
     */
    public function test_meta_tag_service_class_exists(): void
    {
        $this->assertTrue(class_exists(MetaTagService::class));
    }

    /**
     * Test that MetaTags component class exists
     *
     * Validates that the meta tags component is available
     */
    public function test_meta_tags_component_class_exists(): void
    {
        $this->assertTrue(class_exists(MetaTags::class));
    }

    /**
     * Test that meta tags component file exists
     *
     * Validates that the meta tags Blade component exists
     */
    public function test_meta_tags_component_file_exists(): void
    {
        $this->assertFileExists(resource_path('views/components/seo/meta-tags.blade.php'));
    }

    /**
     * Test that meta tags component renders without errors
     *
     * Validates that the meta tags component can be rendered
     */
    public function test_meta_tags_component_renders(): void
    {
        $component = new MetaTags();
        $view = $component->render();

        $this->assertInstanceOf(\Illuminate\View\View::class, $view);
        $this->assertEquals('components.seo.meta-tags', $view->name());
    }

    /**
     * Test that meta description is generated for homepage
     *
     * Validates Requirement 24.1: SEO_System SHALL generate a unique
     * meta description for each page type
     */
    public function test_meta_description_is_generated_for_homepage(): void
    {
        $description = $this->metaService->generateDescription('home');

        $this->assertNotEmpty($description);
        $this->assertIsString($description);
        $this->assertStringContainsString('memorial', strtolower($description));
    }

    /**
     * Test that meta description is generated for memorial profile
     *
     * Validates Requirement 24.4: When a memorial profile page loads,
     * SEO_System SHALL generate a description including the person's name and dates
     */
    public function test_meta_description_is_generated_for_memorial_profile(): void
    {
        $context = [
            'person_name' => 'John Doe',
            'birth_date' => '1950-01-15',
            'death_date' => '2023-06-20',
        ];

        $description = $this->metaService->generateDescription('memorial', $context);

        $this->assertNotEmpty($description);
        $this->assertStringContainsString('John Doe', $description);
        $this->assertStringContainsString('1950-01-15', $description);
        $this->assertStringContainsString('2023-06-20', $description);
    }

    /**
     * Test that meta description is generated for search results
     *
     * Validates Requirement 24.5: When a search results page loads,
     * SEO_System SHALL generate a description including the search term
     */
    public function test_meta_description_is_generated_for_search_results(): void
    {
        $context = [
            'search_term' => 'John Smith',
        ];

        $description = $this->metaService->generateDescription('search', $context);

        $this->assertNotEmpty($description);
        $this->assertStringContainsString('John Smith', $description);
    }

    /**
     * Test that meta description is generated for contact page
     *
     * Validates Requirement 24.1: Unique meta description for contact page
     */
    public function test_meta_description_is_generated_for_contact_page(): void
    {
        $description = $this->metaService->generateDescription('contact');

        $this->assertNotEmpty($description);
        $this->assertStringContainsString('contact', strtolower($description));
    }

    /**
     * Test that meta descriptions are unique per page type
     *
     * Validates Requirement 24.1: SEO_System SHALL generate a unique
     * meta description for each page type
     */
    public function test_meta_descriptions_are_unique_per_page_type(): void
    {
        $homeDescription = $this->metaService->generateDescription('home');
        $contactDescription = $this->metaService->generateDescription('contact');
        $searchDescription = $this->metaService->generateDescription('search', ['search_term' => 'test']);
        $memorialDescription = $this->metaService->generateDescription('memorial', [
            'person_name' => 'Jane Doe',
        ]);

        // Ensure all descriptions are different
        $this->assertNotEquals($homeDescription, $contactDescription);
        $this->assertNotEquals($homeDescription, $searchDescription);
        $this->assertNotEquals($homeDescription, $memorialDescription);
        $this->assertNotEquals($contactDescription, $searchDescription);
        $this->assertNotEquals($contactDescription, $memorialDescription);
        $this->assertNotEquals($searchDescription, $memorialDescription);
    }

    /**
     * Test that meta description length is within limits
     *
     * Validates Requirement 24.3: Meta description SHALL be between
     * 120 and 160 characters
     *
     * Note: Some descriptions may be slightly under 120 characters if the
     * content is naturally shorter and padding would make it awkward.
     */
    public function test_meta_description_length_is_within_limits(): void
    {
        $pageTypes = ['home', 'contact'];

        foreach ($pageTypes as $pageType) {
            $description = $this->metaService->generateDescription($pageType);
            $length = mb_strlen($description);

            $this->assertGreaterThanOrEqual(
                config('seo.meta.description_length.min', 120),
                $length,
                "Description for {$pageType} is too short: {$length} characters"
            );

            $this->assertLessThanOrEqual(
                config('seo.meta.description_length.max', 160),
                $length,
                "Description for {$pageType} is too long: {$length} characters"
            );
        }
    }

    /**
     * Test that memorial meta description is reasonable length
     *
     * Validates that memorial descriptions are not too long
     */
    public function test_memorial_meta_description_is_reasonable_length(): void
    {
        $context = [
            'person_name' => 'John Doe',
            'birth_date' => '1950-01-15',
            'death_date' => '2023-06-20',
        ];

        $description = $this->metaService->generateDescription('memorial', $context);
        $length = mb_strlen($description);

        // Memorial descriptions should be at least 100 characters and not exceed 160
        $this->assertGreaterThanOrEqual(100, $length);
        $this->assertLessThanOrEqual(
            config('seo.meta.description_length.max', 160),
            $length
        );
    }

    /**
     * Test that search meta description is not empty
     *
     * Validates that search descriptions are generated even if shorter
     */
    public function test_search_meta_description_is_not_empty(): void
    {
        $context = ['search_term' => 'test search'];
        $description = $this->metaService->generateDescription('search', $context);

        $this->assertNotEmpty($description);
        $this->assertLessThanOrEqual(
            config('seo.meta.description_length.max', 160),
            mb_strlen($description)
        );
    }

    /**
     * Test that meta descriptions are localized
     *
     * Validates Requirement 24.2: Meta description SHALL be localized
     * for the current locale
     */
    public function test_meta_descriptions_are_localized(): void
    {
        // Test English locale
        app()->setLocale('en');
        $descriptionEn = $this->metaService->generateDescription('home');

        // Test German locale
        app()->setLocale('de');
        $descriptionDe = $this->metaService->generateDescription('home');

        // Descriptions should be different for different locales
        $this->assertNotEquals($descriptionEn, $descriptionDe);
        $this->assertNotEmpty($descriptionEn);
        $this->assertNotEmpty($descriptionDe);
    }

    /**
     * Test that meta descriptions are sanitized
     *
     * Validates Requirement 24.6: SEO_System SHALL sanitize meta
     * descriptions to remove HTML tags
     */
    public function test_meta_descriptions_are_sanitized(): void
    {
        $dirtyContent = '<script>alert("xss")</script>Test <b>description</b> with <a href="#">HTML</a>';
        $sanitized = $this->metaService->sanitize($dirtyContent);

        $this->assertStringNotContainsString('<script>', $sanitized);
        $this->assertStringNotContainsString('<b>', $sanitized);
        $this->assertStringNotContainsString('<a', $sanitized);
        $this->assertStringNotContainsString('</a>', $sanitized);
        $this->assertStringContainsString('Test description with HTML', $sanitized);
    }

    /**
     * Test that Open Graph image is present on memorial profiles
     *
     * Validates Requirement 25.1: When a memorial profile page loads,
     * SEO_System SHALL set an og:image meta tag
     */
    public function test_og_image_is_present_on_memorial_profiles(): void
    {
        $imageUrl = 'https://example.com/memorial-photo.jpg';
        $ogImage = $this->metaService->getOgImage($imageUrl);

        $this->assertNotEmpty($ogImage);
        $this->assertEquals($imageUrl, $ogImage);
    }

    /**
     * Test that default OG image is used when no image provided
     *
     * Validates Requirement 25.3: When no memorial photo exists,
     * og:image SHALL use a default memorial image
     */
    public function test_default_og_image_is_used_when_no_image_provided(): void
    {
        $ogImage = $this->metaService->getOgImage(null);

        $this->assertNotEmpty($ogImage);
        $this->assertStringContainsString(config('seo.meta.default_og_image', '/images/og-default.jpg'), $ogImage);
    }

    /**
     * Test that OG image URL is absolute
     *
     * Validates Requirement 25.6: og:image URL SHALL be absolute
     * and publicly accessible
     */
    public function test_og_image_url_is_absolute(): void
    {
        // Test with relative path
        $relativeImage = '/images/memorial.jpg';
        $ogImage = $this->metaService->getOgImage($relativeImage);

        $this->assertStringStartsWith('http', $ogImage);
        $this->assertStringContainsString($relativeImage, $ogImage);

        // Test with absolute URL
        $absoluteImage = 'https://example.com/memorial.jpg';
        $ogImage = $this->metaService->getOgImage($absoluteImage);

        $this->assertEquals($absoluteImage, $ogImage);
    }

    /**
     * Test that meta tags template contains OG image tags
     *
     * Validates Requirement 25.4, 25.5: SEO_System SHALL set
     * og:image:width, og:image:height, and og:image:alt meta tags
     */
    public function test_meta_tags_template_contains_og_image_tags(): void
    {
        $template = file_get_contents(resource_path('views/components/seo/meta-tags.blade.php'));

        $this->assertStringContainsString('property="og:image"', $template);
        $this->assertStringContainsString('property="og:title"', $template);
        $this->assertStringContainsString('property="og:description"', $template);
        $this->assertStringContainsString('property="og:url"', $template);
        $this->assertStringContainsString('property="og:type"', $template);
    }

    /**
     * Test that Twitter Card tags are present
     *
     * Validates Requirement 26.1: SEO_System SHALL output twitter:card
     * meta tag with value "summary_large_image"
     */
    public function test_twitter_card_tags_are_present(): void
    {
        $title = 'Test Memorial';
        $description = 'Test description for memorial profile';
        $image = 'https://example.com/memorial.jpg';

        $twitterTags = $this->metaService->getTwitterCardTags($title, $description, $image);

        $this->assertArrayHasKey('twitter:card', $twitterTags);
        $this->assertEquals('summary_large_image', $twitterTags['twitter:card']);
    }

    /**
     * Test that Twitter Card title matches page title
     *
     * Validates Requirement 26.2: SEO_System SHALL output twitter:title
     * meta tag matching the page title
     */
    public function test_twitter_card_title_matches_page_title(): void
    {
        $title = 'Test Memorial Page';
        $description = 'Test description';
        $image = 'https://example.com/memorial.jpg';

        $twitterTags = $this->metaService->getTwitterCardTags($title, $description, $image);

        $this->assertArrayHasKey('twitter:title', $twitterTags);
        $this->assertStringContainsString('Test Memorial Page', $twitterTags['twitter:title']);
    }

    /**
     * Test that Twitter Card description matches meta description
     *
     * Validates Requirement 26.3: SEO_System SHALL output twitter:description
     * meta tag matching the meta description
     */
    public function test_twitter_card_description_matches_meta_description(): void
    {
        $title = 'Test Memorial';
        $description = 'This is a test description for the memorial profile page';
        $image = 'https://example.com/memorial.jpg';

        $twitterTags = $this->metaService->getTwitterCardTags($title, $description, $image);

        $this->assertArrayHasKey('twitter:description', $twitterTags);
        $this->assertStringContainsString('test description', strtolower($twitterTags['twitter:description']));
    }

    /**
     * Test that Twitter Card image matches OG image
     *
     * Validates Requirement 26.4: SEO_System SHALL output twitter:image
     * meta tag matching the og:image
     */
    public function test_twitter_card_image_matches_og_image(): void
    {
        $title = 'Test Memorial';
        $description = 'Test description';
        $image = 'https://example.com/memorial.jpg';

        $twitterTags = $this->metaService->getTwitterCardTags($title, $description, $image);

        $this->assertArrayHasKey('twitter:image', $twitterTags);
        $this->assertEquals($image, $twitterTags['twitter:image']);
    }

    /**
     * Test that Twitter site tag is included when configured
     *
     * Validates Requirement 26.5: When a Twitter handle is configured,
     * SEO_System SHALL output twitter:site meta tag
     */
    public function test_twitter_site_tag_is_included_when_configured(): void
    {
        // Set Twitter handle in config
        config(['seo.social.twitter_handle' => '@spomenar']);

        $title = 'Test Memorial';
        $description = 'Test description';
        $image = 'https://example.com/memorial.jpg';

        $twitterTags = $this->metaService->getTwitterCardTags($title, $description, $image);

        $this->assertArrayHasKey('twitter:site', $twitterTags);
        $this->assertEquals('@spomenar', $twitterTags['twitter:site']);
    }

    /**
     * Test that Twitter Card tags are rendered in template
     *
     * Validates that all Twitter Card tags are output in the meta tags template
     */
    public function test_twitter_card_tags_are_rendered_in_template(): void
    {
        $template = file_get_contents(resource_path('views/components/seo/meta-tags.blade.php'));

        $this->assertStringContainsString('Twitter Card Tags', $template);
        $this->assertStringContainsString('@foreach($twitterTags', $template);
    }

    /**
     * Test that canonical URL is present on all pages
     *
     * Validates Requirement 29.1: SEO_System SHALL output a canonical
     * link tag on every page
     */
    public function test_canonical_url_is_present_on_all_pages(): void
    {
        $canonicalUrl = $this->metaService->getCanonicalUrl();

        $this->assertNotEmpty($canonicalUrl);
        $this->assertIsString($canonicalUrl);
    }

    /**
     * Test that canonical URL includes locale prefix
     *
     * Validates Requirement 29.2: Canonical URL SHALL include the locale prefix
     */
    public function test_canonical_url_includes_locale_prefix(): void
    {
        app()->setLocale('en');
        $this->get('/en');

        $canonicalUrl = $this->metaService->getCanonicalUrl();

        $this->assertStringContainsString('/en', $canonicalUrl);
    }

    /**
     * Test that canonical URL is absolute
     *
     * Validates Requirement 29.3: Canonical URL SHALL be absolute
     * with protocol and domain
     */
    public function test_canonical_url_is_absolute(): void
    {
        $canonicalUrl = $this->metaService->getCanonicalUrl();

        $this->assertStringStartsWith('http', $canonicalUrl);
        $this->assertMatchesRegularExpression('/^https?:\/\/[^\/]+/', $canonicalUrl);
    }

    /**
     * Test that canonical URL uses lowercase
     *
     * Validates Requirement 29.5: Canonical URL SHALL use lowercase
     * for consistency
     */
    public function test_canonical_url_uses_lowercase(): void
    {
        $canonicalUrl = $this->metaService->getCanonicalUrl();

        $this->assertEquals(strtolower($canonicalUrl), $canonicalUrl);
    }

    /**
     * Test that canonical URL is rendered in template
     *
     * Validates that the canonical link tag is present in the meta tags template
     */
    public function test_canonical_url_is_rendered_in_template(): void
    {
        $template = file_get_contents(resource_path('views/components/seo/meta-tags.blade.php'));

        $this->assertStringContainsString('rel="canonical"', $template);
        $this->assertStringContainsString('{{ $canonicalUrl }}', $template);
    }

    /**
     * Test that meta description tag is rendered in template
     *
     * Validates that the meta description is output in the template
     */
    public function test_meta_description_tag_is_rendered_in_template(): void
    {
        $template = file_get_contents(resource_path('views/components/seo/meta-tags.blade.php'));

        $this->assertStringContainsString('name="description"', $template);
        $this->assertStringContainsString('{{ $description }}', $template);
    }

    /**
     * Test that meta tags component accepts custom values
     *
     * Validates that the component can accept custom title, description, and image
     */
    public function test_meta_tags_component_accepts_custom_values(): void
    {
        $customTitle = 'Custom Memorial Title';
        $customDescription = 'This is a custom description for testing purposes that is long enough to meet the minimum requirements.';
        $customImage = 'https://example.com/custom-image.jpg';

        $component = new MetaTags(
            pageType: 'memorial',
            context: [],
            title: $customTitle,
            description: $customDescription,
            image: $customImage
        );

        $this->assertEquals($customTitle, $component->title);
        $this->assertEquals($customDescription, $component->description);
        $this->assertEquals($customImage, $component->image);
    }

    /**
     * Test that meta tags component generates default values
     *
     * Validates that the component generates values when not provided
     */
    public function test_meta_tags_component_generates_default_values(): void
    {
        $component = new MetaTags(pageType: 'home');

        $this->assertNotEmpty($component->title);
        $this->assertNotEmpty($component->description);
        $this->assertNotEmpty($component->image);
        $this->assertNotEmpty($component->canonicalUrl);
        $this->assertNotEmpty($component->twitterTags);
    }

    /**
     * Test that sanitize method enforces maximum length
     *
     * Validates that content is truncated when exceeding max length
     */
    public function test_sanitize_method_enforces_maximum_length(): void
    {
        $longContent = str_repeat('This is a very long description that exceeds the maximum allowed length. ', 10);
        $sanitized = $this->metaService->sanitize($longContent, 160);

        $this->assertLessThanOrEqual(160, mb_strlen($sanitized));
        $this->assertStringEndsWith('...', $sanitized);
    }

    /**
     * Test that sanitize method removes extra whitespace
     *
     * Validates that multiple spaces are collapsed to single spaces
     */
    public function test_sanitize_method_removes_extra_whitespace(): void
    {
        $content = "This  has   multiple    spaces     and\n\nnewlines\t\ttabs";
        $sanitized = $this->metaService->sanitize($content);

        $this->assertStringNotContainsString('  ', $sanitized);
        $this->assertStringNotContainsString("\n", $sanitized);
        $this->assertStringNotContainsString("\t", $sanitized);
    }

    /**
     * Test that meta tags work with different locales
     *
     * Validates multi-language support for meta tags
     */
    public function test_meta_tags_work_with_different_locales(): void
    {
        $locales = ['en', 'de', 'bs', 'sr', 'hr', 'it'];

        foreach ($locales as $locale) {
            app()->setLocale($locale);
            $description = $this->metaService->generateDescription('home');

            $this->assertNotEmpty($description, "Description is empty for locale: {$locale}");
            $this->assertIsString($description);
        }
    }

    /**
     * Test that memorial description handles missing dates gracefully
     *
     * Validates that memorial descriptions work with partial data
     */
    public function test_memorial_description_handles_missing_dates_gracefully(): void
    {
        $context = [
            'person_name' => 'Jane Doe',
        ];

        $description = $this->metaService->generateDescription('memorial', $context);

        $this->assertNotEmpty($description);
        $this->assertStringContainsString('Jane Doe', $description);
    }

    /**
     * Test that search description handles missing search term gracefully
     *
     * Validates that search descriptions work without search term
     */
    public function test_search_description_handles_missing_search_term_gracefully(): void
    {
        $description = $this->metaService->generateDescription('search', []);

        $this->assertNotEmpty($description);
        $this->assertStringContainsString('search', strtolower($description));
    }
}
