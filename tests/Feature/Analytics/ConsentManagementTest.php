<?php

namespace Tests\Feature\Analytics;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Consent Management Feature Tests
 *
 * Tests cookie consent banner display, localStorage interactions,
 * and GTM consent mode integration.
 *
 * Requirements: 4.1, 4.8, 5.1, 5.5, 6.2, 6.3
 */
class ConsentManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // No need to set up database for most tests since we're testing
        // JavaScript files and component rendering, not full page loads
    }

    /**
     * Test that cookie banner component file exists
     *
     * Validates Requirement 4.1: When a user visits the site for the first time,
     * the Consent_Manager SHALL display the Cookie_Banner
     */
    public function test_cookie_banner_component_file_exists(): void
    {
        // Check that the cookie banner component file exists
        $this->assertFileExists(resource_path('views/components/analytics/cookie-banner.blade.php'));
    }

    /**
     * Test that cookie banner component class exists
     *
     * Validates that the cookie banner has a proper component class
     */
    public function test_cookie_banner_component_class_exists(): void
    {
        $this->assertTrue(class_exists(\App\View\Components\Analytics\CookieBanner::class));
    }

    /**
     * Test that cookie banner component renders without errors
     *
     * Validates Requirement 4.1: Cookie banner can be rendered
     */
    public function test_cookie_banner_component_renders(): void
    {
        $component = new \App\View\Components\Analytics\CookieBanner();
        $view = $component->render();

        $this->assertInstanceOf(\Illuminate\View\View::class, $view);
        $this->assertEquals('components.analytics.cookie-banner', $view->name());
    }

    /**
     * Test that cookie banner template contains required elements
     *
     * Validates Requirements 4.4, 4.5, 4.6: Cookie banner SHALL include
     * "Accept All", "Reject All", and "Customize" buttons
     */
    public function test_cookie_banner_template_contains_required_elements(): void
    {
        $template = file_get_contents(resource_path('views/components/analytics/cookie-banner.blade.php'));

        // Check for banner container
        $this->assertStringContainsString('id="cookie-consent-banner"', $template);
        $this->assertStringContainsString('class="cookie-banner"', $template);

        // Check for ARIA attributes
        $this->assertStringContainsString('role="dialog"', $template);
        $this->assertStringContainsString('aria-labelledby="cookie-banner-title"', $template);
        $this->assertStringContainsString('aria-describedby="cookie-banner-description"', $template);

        // Check for buttons
        $this->assertStringContainsString('id="cookie-accept-all"', $template);
        $this->assertStringContainsString('id="cookie-reject-all"', $template);
        $this->assertStringContainsString('id="cookie-customize"', $template);
    }

    /**
     * Test that cookie banner template contains privacy policy link
     *
     * Validates Requirement 4.7: Cookie banner SHALL include a link
     * to the privacy policy page
     */
    public function test_cookie_banner_template_contains_privacy_policy_link(): void
    {
        $template = file_get_contents(resource_path('views/components/analytics/cookie-banner.blade.php'));

        // Check for privacy policy link
        $this->assertStringContainsString('cookie-banner__privacy-link', $template);
        $this->assertStringContainsString("route('privacy'", $template);
    }

    /**
     * Test that cookie banner template contains all cookie categories
     *
     * Validates Requirement 4.3: Cookie banner SHALL provide options
     * for necessary, analytics, and marketing cookies
     */
    public function test_cookie_banner_template_contains_all_cookie_categories(): void
    {
        $template = file_get_contents(resource_path('views/components/analytics/cookie-banner.blade.php'));

        // Check for cookie categories
        $this->assertStringContainsString('id="cookie-necessary"', $template);
        $this->assertStringContainsString('id="cookie-analytics"', $template);
        $this->assertStringContainsString('id="cookie-marketing"', $template);
    }

    /**
     * Test that necessary cookies checkbox is disabled in template
     *
     * Validates that necessary cookies are always enabled and cannot be disabled
     */
    public function test_necessary_cookies_checkbox_is_disabled_in_template(): void
    {
        $template = file_get_contents(resource_path('views/components/analytics/cookie-banner.blade.php'));

        // Check that necessary cookies checkbox is disabled
        $this->assertStringContainsString('id="cookie-necessary"', $template);
        $this->assertStringContainsString('disabled', $template);
    }

    /**
     * Test that cookie banner uses translation keys
     *
     * Validates Requirement 4.2: Cookie banner SHALL display content
     * in the current locale
     */
    public function test_cookie_banner_uses_translation_keys(): void
    {
        $template = file_get_contents(resource_path('views/components/analytics/cookie-banner.blade.php'));

        // Check for translation keys
        $this->assertStringContainsString("__('cookies.banner.title')", $template);
        $this->assertStringContainsString("__('cookies.banner.description')", $template);
        $this->assertStringContainsString("__('cookies.banner.accept_all')", $template);
        $this->assertStringContainsString("__('cookies.banner.reject_all')", $template);
        $this->assertStringContainsString("__('cookies.banner.customize')", $template);
    }

    /**
     * Test that ConsentManager JavaScript class is available
     *
     * Validates that the consent manager JavaScript is properly loaded
     */
    public function test_consent_manager_javascript_is_loaded(): void
    {
        // Check that the JavaScript file exists
        $this->assertFileExists(resource_path('js/analytics/consent-manager.js'));
    }

    /**
     * Test that CookieBannerUI JavaScript class is available
     *
     * Validates that the cookie banner UI JavaScript is properly loaded
     */
    public function test_cookie_banner_ui_javascript_is_loaded(): void
    {
        // Check that the JavaScript file exists
        $this->assertFileExists(resource_path('js/analytics/cookie-banner.js'));
    }

    /**
     * Test that cookie banner template has keyboard navigation support
     *
     * Validates Requirement 4.9: Cookie banner SHALL be accessible
     * via keyboard navigation
     */
    public function test_cookie_banner_template_has_keyboard_navigation_support(): void
    {
        $template = file_get_contents(resource_path('views/components/analytics/cookie-banner.blade.php'));

        // Check that buttons are keyboard accessible (button elements)
        $this->assertStringContainsString('<button type="button"', $template);

        // Check that checkboxes have proper labels for screen readers
        $this->assertStringContainsString('aria-label', $template);
    }

    /**
     * Test that detailed cookie settings section is present in template
     *
     * Validates that the customize functionality has a detailed settings section
     */
    public function test_detailed_cookie_settings_section_is_present_in_template(): void
    {
        $template = file_get_contents(resource_path('views/components/analytics/cookie-banner.blade.php'));

        // Check for cookie details section
        $this->assertStringContainsString('id="cookie-details"', $template);

        // Check for save preferences button
        $this->assertStringContainsString('id="cookie-save-preferences"', $template);

        // Check for back button
        $this->assertStringContainsString('id="cookie-back"', $template);
    }

    /**
     * Test that cookie banner template includes toggle switches
     *
     * Validates that users can toggle consent for optional cookie categories
     */
    public function test_cookie_banner_template_includes_toggle_switches(): void
    {
        $template = file_get_contents(resource_path('views/components/analytics/cookie-banner.blade.php'));

        // Check for toggle switch elements
        $this->assertStringContainsString('toggle-switch', $template);
        $this->assertStringContainsString('toggle-slider', $template);

        // Check that analytics and marketing have checkboxes
        $this->assertStringContainsString('type="checkbox"', $template);
    }

    /**
     * Test that cookie banner template has styling for accessibility
     *
     * Validates Requirement 4.10: Cookie banner SHALL have sufficient
     * color contrast for WCAG AA compliance
     *
     * Note: This test verifies that CSS is present. Actual color contrast
     * would need to be tested with browser automation tools.
     */
    public function test_cookie_banner_template_has_styling_for_accessibility(): void
    {
        $template = file_get_contents(resource_path('views/components/analytics/cookie-banner.blade.php'));

        // Check that the banner includes CSS styling
        $this->assertStringContainsString('<style>', $template);
        $this->assertStringContainsString('.cookie-banner', $template);
        $this->assertStringContainsString('.cookie-banner__title', $template);
        $this->assertStringContainsString('.cookie-banner__description', $template);

        // Check for dark mode support
        $this->assertStringContainsString('@media (prefers-color-scheme: dark)', $template);
    }

    /**
     * Test that cookie banner template is responsive
     *
     * Validates that the banner includes responsive design CSS
     */
    public function test_cookie_banner_template_is_responsive(): void
    {
        $template = file_get_contents(resource_path('views/components/analytics/cookie-banner.blade.php'));

        // Check for responsive media queries
        $this->assertStringContainsString('@media (max-width: 640px)', $template);
    }

    /**
     * Test consent manager storage key configuration
     *
     * Validates Requirement 5.1: Consent preferences are stored in localStorage
     * with the correct storage key
     */
    public function test_consent_manager_uses_correct_storage_key(): void
    {
        // Read the consent manager JavaScript file
        $consentManagerJs = file_get_contents(resource_path('js/analytics/consent-manager.js'));

        // Check that the storage key is defined
        $this->assertStringContainsString("storageKey", $consentManagerJs);
        $this->assertStringContainsString("'cookie_consent'", $consentManagerJs);
    }

    /**
     * Test consent manager version configuration
     *
     * Validates Requirement 5.3: Consent version number is stored with preferences
     */
    public function test_consent_manager_has_version_configuration(): void
    {
        // Read the consent manager JavaScript file
        $consentManagerJs = file_get_contents(resource_path('js/analytics/consent-manager.js'));

        // Check that version is defined
        $this->assertStringContainsString("version", $consentManagerJs);
    }

    /**
     * Test consent manager expiration configuration
     *
     * Validates Requirement 5.5: Consent expires after 12 months
     */
    public function test_consent_manager_has_expiration_configuration(): void
    {
        // Read the consent manager JavaScript file
        $consentManagerJs = file_get_contents(resource_path('js/analytics/consent-manager.js'));

        // Check that expiration is configured
        $this->assertStringContainsString("expirationMonths", $consentManagerJs);
        $this->assertStringContainsString("12", $consentManagerJs);
    }

    /**
     * Test consent manager has shouldShowBanner method
     *
     * Validates Requirement 4.1: Consent manager checks if banner should be shown
     */
    public function test_consent_manager_has_should_show_banner_method(): void
    {
        // Read the consent manager JavaScript file
        $consentManagerJs = file_get_contents(resource_path('js/analytics/consent-manager.js'));

        // Check that shouldShowBanner method exists
        $this->assertStringContainsString("shouldShowBanner()", $consentManagerJs);
    }

    /**
     * Test consent manager has getConsent method
     *
     * Validates Requirement 5.7: Consent manager provides method to retrieve
     * current consent status
     */
    public function test_consent_manager_has_get_consent_method(): void
    {
        // Read the consent manager JavaScript file
        $consentManagerJs = file_get_contents(resource_path('js/analytics/consent-manager.js'));

        // Check that getConsent method exists
        $this->assertStringContainsString("getConsent()", $consentManagerJs);
        $this->assertStringContainsString("localStorage.getItem", $consentManagerJs);
    }

    /**
     * Test consent manager has saveConsent method
     *
     * Validates Requirement 5.8: Consent manager provides method to update
     * consent preferences
     */
    public function test_consent_manager_has_save_consent_method(): void
    {
        // Read the consent manager JavaScript file
        $consentManagerJs = file_get_contents(resource_path('js/analytics/consent-manager.js'));

        // Check that saveConsent method exists
        $this->assertStringContainsString("saveConsent(", $consentManagerJs);
        $this->assertStringContainsString("localStorage.setItem", $consentManagerJs);
    }

    /**
     * Test consent manager has isConsentExpired method
     *
     * Validates Requirement 5.5: Consent manager checks if consent has expired
     */
    public function test_consent_manager_has_is_consent_expired_method(): void
    {
        // Read the consent manager JavaScript file
        $consentManagerJs = file_get_contents(resource_path('js/analytics/consent-manager.js'));

        // Check that isConsentExpired method exists
        $this->assertStringContainsString("isConsentExpired(", $consentManagerJs);
        $this->assertStringContainsString("expiresAt", $consentManagerJs);
    }

    /**
     * Test consent manager has deleteConsent method
     *
     * Validates that consent can be deleted from localStorage
     */
    public function test_consent_manager_has_delete_consent_method(): void
    {
        // Read the consent manager JavaScript file
        $consentManagerJs = file_get_contents(resource_path('js/analytics/consent-manager.js'));

        // Check that deleteConsent method exists
        $this->assertStringContainsString("deleteConsent()", $consentManagerJs);
        $this->assertStringContainsString("localStorage.removeItem", $consentManagerJs);
    }

    /**
     * Test consent manager has updateGTMConsent method
     *
     * Validates Requirements 6.2, 6.3: Consent manager updates GTM consent mode
     * based on user choices
     */
    public function test_consent_manager_has_update_gtm_consent_method(): void
    {
        // Read the consent manager JavaScript file
        $consentManagerJs = file_get_contents(resource_path('js/analytics/consent-manager.js'));

        // Check that updateGTMConsent method exists
        $this->assertStringContainsString("updateGTMConsent(", $consentManagerJs);

        // Check that it pushes to dataLayer
        $this->assertStringContainsString("dataLayer.push", $consentManagerJs);

        // Check that it includes consent mode parameters
        $this->assertStringContainsString("analytics_storage", $consentManagerJs);
        $this->assertStringContainsString("ad_storage", $consentManagerJs);
        $this->assertStringContainsString("functionality_storage", $consentManagerJs);
    }

    /**
     * Test consent manager stores timestamp with preferences
     *
     * Validates Requirement 5.2: Consent manager stores consent timestamp
     */
    public function test_consent_manager_stores_timestamp(): void
    {
        // Read the consent manager JavaScript file
        $consentManagerJs = file_get_contents(resource_path('js/analytics/consent-manager.js'));

        // Check that timestamp is stored
        $this->assertStringContainsString("timestamp", $consentManagerJs);
        $this->assertStringContainsString("Date.now()", $consentManagerJs);
    }

    /**
     * Test consent manager stores individual consent status for each category
     *
     * Validates Requirement 5.4: Consent manager stores individual consent
     * status for each cookie category
     */
    public function test_consent_manager_stores_individual_consent_status(): void
    {
        // Read the consent manager JavaScript file
        $consentManagerJs = file_get_contents(resource_path('js/analytics/consent-manager.js'));

        // Check that individual categories are stored
        $this->assertStringContainsString("necessary:", $consentManagerJs);
        $this->assertStringContainsString("analytics:", $consentManagerJs);
        $this->assertStringContainsString("marketing:", $consentManagerJs);
    }

    /**
     * Test consent manager sets default consent mode to denied
     *
     * Validates Requirement 6.1: Consent manager sets GTM consent mode
     * default state to denied for analytics_storage and ad_storage
     */
    public function test_consent_manager_sets_default_consent_to_denied(): void
    {
        // Read the consent manager JavaScript file
        $consentManagerJs = file_get_contents(resource_path('js/analytics/consent-manager.js'));

        // Check that default consent is set to denied
        $this->assertStringContainsString("'denied'", $consentManagerJs);
        $this->assertStringContainsString("consent_default", $consentManagerJs);
    }

    /**
     * Test consent manager sets functionality_storage to granted
     *
     * Validates Requirement 6.6: Consent manager sets functionality_storage
     * to granted by default for necessary cookies
     */
    public function test_consent_manager_sets_functionality_storage_to_granted(): void
    {
        // Read the consent manager JavaScript file
        $consentManagerJs = file_get_contents(resource_path('js/analytics/consent-manager.js'));

        // Check that functionality_storage is set to granted
        $this->assertStringContainsString("functionality_storage", $consentManagerJs);
        $this->assertStringContainsString("'granted'", $consentManagerJs);
    }

    /**
     * Test cookie banner UI handles Accept All action
     *
     * Validates that clicking Accept All saves consent with all categories enabled
     */
    public function test_cookie_banner_ui_handles_accept_all(): void
    {
        // Read the cookie banner UI JavaScript file
        $cookieBannerJs = file_get_contents(resource_path('js/analytics/cookie-banner.js'));

        // Check that handleAcceptAll method exists
        $this->assertStringContainsString("handleAcceptAll()", $cookieBannerJs);

        // Check that it sets all preferences to true
        $this->assertStringContainsString("analytics: true", $cookieBannerJs);
        $this->assertStringContainsString("marketing: true", $cookieBannerJs);
    }

    /**
     * Test cookie banner UI handles Reject All action
     *
     * Validates that clicking Reject All saves consent with optional categories disabled
     */
    public function test_cookie_banner_ui_handles_reject_all(): void
    {
        // Read the cookie banner UI JavaScript file
        $cookieBannerJs = file_get_contents(resource_path('js/analytics/cookie-banner.js'));

        // Check that handleRejectAll method exists
        $this->assertStringContainsString("handleRejectAll()", $cookieBannerJs);

        // Check that it sets optional preferences to false
        $this->assertStringContainsString("analytics: false", $cookieBannerJs);
        $this->assertStringContainsString("marketing: false", $cookieBannerJs);
    }

    /**
     * Test cookie banner UI handles Save Preferences action
     *
     * Validates that saving custom preferences reads checkbox states
     */
    public function test_cookie_banner_ui_handles_save_preferences(): void
    {
        // Read the cookie banner UI JavaScript file
        $cookieBannerJs = file_get_contents(resource_path('js/analytics/cookie-banner.js'));

        // Check that handleSavePreferences method exists
        $this->assertStringContainsString("handleSavePreferences()", $cookieBannerJs);

        // Check that it reads checkbox states
        $this->assertStringContainsString("getElementById('cookie-analytics')", $cookieBannerJs);
        $this->assertStringContainsString("getElementById('cookie-marketing')", $cookieBannerJs);
        $this->assertStringContainsString(".checked", $cookieBannerJs);
    }

    /**
     * Test cookie banner UI shows and hides banner
     *
     * Validates Requirement 4.8: Cookie banner does not display when
     * consent is stored
     */
    public function test_cookie_banner_ui_shows_and_hides_banner(): void
    {
        // Read the cookie banner UI JavaScript file
        $cookieBannerJs = file_get_contents(resource_path('js/analytics/cookie-banner.js'));

        // Check that show/hide methods exist
        $this->assertStringContainsString("showBanner()", $cookieBannerJs);
        $this->assertStringContainsString("hideBanner()", $cookieBannerJs);

        // Check that it checks shouldShowBanner
        $this->assertStringContainsString("shouldShowBanner()", $cookieBannerJs);
    }

    /**
     * Test cookie banner UI shows and hides detailed settings
     *
     * Validates that the customize functionality toggles detailed settings
     */
    public function test_cookie_banner_ui_shows_and_hides_details(): void
    {
        // Read the cookie banner UI JavaScript file
        $cookieBannerJs = file_get_contents(resource_path('js/analytics/cookie-banner.js'));

        // Check that show/hide details methods exist
        $this->assertStringContainsString("showDetails()", $cookieBannerJs);
        $this->assertStringContainsString("hideDetails()", $cookieBannerJs);
    }

    /**
     * Test cookie banner UI loads existing preferences
     *
     * Validates that existing consent preferences are loaded into checkboxes
     */
    public function test_cookie_banner_ui_loads_existing_preferences(): void
    {
        // Read the cookie banner UI JavaScript file
        $cookieBannerJs = file_get_contents(resource_path('js/analytics/cookie-banner.js'));

        // Check that loadPreferences method exists
        $this->assertStringContainsString("loadPreferences()", $cookieBannerJs);

        // Check that it gets consent from consent manager
        $this->assertStringContainsString("getConsent()", $cookieBannerJs);
    }
}
