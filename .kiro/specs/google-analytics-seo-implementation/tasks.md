# Implementation Plan: Google Analytics & SEO Implementation

## Overview

This implementation plan converts the Google Analytics & SEO feature design into actionable coding tasks. The feature includes GTM integration, GA4 tracking, GDPR-compliant cookie consent management across 6 locales, 12 event types, structured data for SEO, and comprehensive meta tag optimization for a Laravel 11 memorial application.

The implementation follows a phased approach: foundation setup, GTM integration, cookie consent system, event tracking, SEO structured data, meta tags optimization, sitemap enhancement, and comprehensive testing.

## Tasks

### Phase 1: Foundation & Configuration

- [x] 1. Set up configuration files and environment variables
  - [x] 1.1 Create analytics configuration file
    - Create `config/analytics.php` with GTM, GA4, consent, and CSP settings
    - Define environment-based GTM container ID selection logic
    - Configure consent version, expiration, and storage key
    - _Requirements: 1.3, 1.4, 1.5, 1.6, 34.1, 34.2, 34.3, 34.4_
  
  - [x] 1.2 Create SEO configuration file
    - Create `config/seo.php` with site info, social media, structured data, and sitemap settings
    - Define priority and changefreq values for different page types
    - Configure meta description length constraints
    - _Requirements: 20.2, 20.3, 20.4, 27.2, 27.3, 27.4, 33.1, 33.2_
  
  - [x] 1.3 Update .env.example with required variables
    - Add GTM_ID, GTM_ID_STAGING, GA4_MEASUREMENT_ID
    - Add ANALYTICS_ENABLED, ANALYTICS_DEBUG_MODE flags
    - Add GOOGLE_SEARCH_CONSOLE_VERIFICATION
    - Add SITE_NAME, SITE_URL, social media URLs
    - _Requirements: 1.6, 34.1, 34.2, 34.3, 34.4, 33.2_


### Phase 2: GTM & Data Layer Integration

- [x] 2. Implement GTM service and Blade components
  - [x] 2.1 Create GTMService class
    - Create `app/Services/Analytics/GTMService.php`
    - Implement getContainerId() method with environment-based logic
    - Implement isEnabled() to check ANALYTICS_ENABLED flag
    - Implement isDebugMode() to check ANALYTICS_DEBUG_MODE flag
    - Implement getHeadScript() to generate GTM head script HTML
    - Implement getBodyNoScript() to generate GTM noscript iframe HTML
    - Implement getCspDirectives() to return CSP directives for GTM domains
    - _Requirements: 1.1, 1.3, 1.4, 1.5, 1.6, 1.8, 34.5, 37.1, 37.2_
  
  - [x] 2.2 Create GTM Blade components
    - Create `resources/views/components/analytics/gtm-head.blade.php`
    - Create `resources/views/components/analytics/gtm-body.blade.php`
    - Create component classes `app/View/Components/Analytics/GTMHead.php` and `GTMBody.php`
    - Implement nonce support for CSP compatibility
    - _Requirements: 1.1, 1.2, 37.3, 37.4_
  
  - [x] 2.3 Create DataLayerService class
    - Create `app/Services/Analytics/DataLayerService.php`
    - Implement getInitialState() to generate page context data
    - Implement getPageType() to determine page type from route
    - Implement getUserType() to return 'guest' or 'registered'
    - Implement getRegion() to map locale to region code
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5_
  
  - [x] 2.4 Create data layer initialization Blade component
    - Create `resources/views/components/analytics/data-layer-init.blade.php`
    - Initialize dataLayer array with page context before GTM loads
    - Create component class `app/View/Components/Analytics/DataLayerInit.php`
    - _Requirements: 1.7, 2.1, 2.6_
  
  - [x] 2.5 Integrate GTM and data layer into main layout
    - Update `resources/views/layouts/app.blade.php` head section
    - Add data-layer-init component before GTM
    - Add gtm-head component in head section
    - Add gtm-body component after opening body tag
    - Add resource hints for GTM, GA4, and DoubleClick domains
    - Add Google Search Console verification meta tag
    - _Requirements: 1.1, 1.2, 1.7, 32.1, 32.2, 32.3, 32.4, 32.5, 33.1, 33.3_


### Phase 3: Cookie Consent System

- [x] 3. Implement cookie consent manager JavaScript
  - [x] 3.1 Create ConsentManager class
    - Create `resources/js/analytics/consent-manager.js`
    - Implement shouldShowBanner() to check if banner should display
    - Implement getConsent() to retrieve stored consent from localStorage
    - Implement saveConsent() to store consent preferences with timestamp and version
    - Implement isConsentExpired() to check 12-month expiration
    - Implement deleteConsent() to remove stored preferences
    - Implement updateGTMConsent() to push consent mode updates to dataLayer
    - _Requirements: 4.1, 4.8, 5.1, 5.2, 5.3, 5.4, 5.5, 5.6, 5.7, 5.8, 6.1, 6.2, 6.3, 6.4, 6.5, 6.6, 6.7_
  
  - [x] 3.2 Create cookie banner Blade component
    - Create `resources/views/components/analytics/cookie-banner.blade.php`
    - Add banner structure with title, description, and action buttons
    - Include "Accept All", "Reject All", and "Customize" buttons
    - Add detailed cookie category toggles in expandable section
    - Include link to privacy policy page
    - Ensure keyboard navigation support and WCAG AA color contrast
    - Create component class `app/View/Components/Analytics/CookieBanner.php`
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7, 4.9, 4.10, 39.1, 39.2_
  
  - [x] 3.3 Create cookie banner JavaScript interactions
    - Create `resources/js/analytics/cookie-banner.js`
    - Attach event listeners to Accept All, Reject All, and Customize buttons
    - Implement banner show/hide logic
    - Implement detailed settings toggle
    - Call ConsentManager methods on user actions
    - _Requirements: 4.4, 4.5, 4.6, 5.7, 5.8_
  
  - [x] 3.4 Create translation files for all locales
    - Create `resources/lang/bs/cookies.php` (Bosnian)
    - Create `resources/lang/sr/cookies.php` (Serbian)
    - Create `resources/lang/hr/cookies.php` (Croatian)
    - Create `resources/lang/de/cookies.php` (German)
    - Create `resources/lang/en/cookies.php` (English)
    - Create `resources/lang/it/cookies.php` (Italian)
    - Include translations for banner title, description, buttons, and cookie categories
    - _Requirements: 4.2, 38.1, 38.2, 38.3, 38.4, 38.5, 38.6, 38.7_
  
  - [x] 3.5 Create cookie settings page
    - Create route for cookie settings page in `routes/web.php`
    - Create `app/Http/Controllers/CookieSettingsController.php`
    - Create view `resources/views/pages/cookie-settings.blade.php`
    - Display current consent status for each category
    - Add toggle switches for analytics and marketing cookies
    - Display cookie descriptions and list of cookies per category
    - Include link to privacy policy
    - Implement real-time preference updates without page reload
    - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5, 7.6, 7.7, 7.8, 39.3_
  
  - [x] 3.6 Add cookie settings link to footer
    - Update footer template to include cookie settings link
    - Ensure link uses current locale
    - _Requirements: 7.1_


### Phase 4: Event Tracking Implementation

- [x] 4. Implement event tracking system
  - [x] 4.1 Create DataLayerManager class
    - Create `resources/js/analytics/data-layer.js`
    - Implement push() method to add events to dataLayer
    - Implement getState() to retrieve current dataLayer state
    - Implement updatePageContext() to update page-level data
    - _Requirements: 2.6, 2.7_
  
  - [x] 4.2 Create EventTracker class
    - Create `resources/js/analytics/event-tracker.js`
    - Implement canTrack() private method to check consent status
    - Implement sanitizeParams() to clean event parameters
    - Implement debugLog() to log events in debug mode
    - _Requirements: 35.1, 35.2, 35.3_
  
  - [x] 4.3 Implement page view tracking
    - Add trackPageView() method to EventTracker
    - Include page_path, page_title, page_locale, page_type parameters
    - Check analytics consent before sending
    - _Requirements: 8.1, 8.2, 8.3, 8.4, 8.5, 8.6_
  
  - [x] 4.4 Implement memorial profile view tracking
    - Add trackMemorialView() method to EventTracker
    - Include memorial_id, memorial_slug, locale, is_public parameters
    - Check analytics consent before sending
    - _Requirements: 9.1, 9.2, 9.3, 9.4, 9.5, 9.6_
  
  - [x] 4.5 Implement search query tracking
    - Add trackSearch() method to EventTracker
    - Include search_term, results_count, locale parameters
    - Check analytics consent before sending
    - _Requirements: 10.1, 10.2, 10.3, 10.4, 10.5_
  
  - [x] 4.6 Implement form submission tracking
    - Add trackFormSubmit() method to EventTracker
    - Include form_type, locale, success, error_type parameters
    - Check analytics consent before sending
    - _Requirements: 11.1, 11.2, 11.3, 11.4, 11.5, 11.6, 11.7_
  
  - [x] 4.7 Implement user registration tracking
    - Add trackSignUp() method to EventTracker
    - Include locale, registration_method parameters
    - Check analytics consent before sending
    - _Requirements: 12.1, 12.2, 12.3, 12.4_
  
  - [x] 4.8 Implement memorial creation tracking
    - Add trackMemorialCreation() method to EventTracker
    - Include locale, is_public parameters
    - Check analytics consent before sending
    - _Requirements: 13.1, 13.2, 13.3, 13.4_
  
  - [x] 4.9 Implement media upload tracking
    - Add trackMediaUpload() method to EventTracker
    - Include media_type, memorial_id, file_size_kb parameters
    - Check analytics consent before sending
    - _Requirements: 14.1, 14.2, 14.3, 14.4, 14.5_
  
  - [x] 4.10 Implement tribute submission tracking
    - Add trackTributeSubmit() method to EventTracker
    - Include memorial_id, locale, tribute_type parameters
    - Check analytics consent before sending
    - _Requirements: 15.1, 15.2, 15.3, 15.4, 15.5_
  
  - [x] 4.11 Implement navigation click tracking
    - Add trackNavigationClick() method to EventTracker
    - Include menu_item, destination_url, locale parameters
    - Check analytics consent before sending
    - _Requirements: 16.1, 16.2, 16.3, 16.4, 16.5_
  
  - [x] 4.12 Implement external link click tracking
    - Add trackOutboundClick() method to EventTracker
    - Include link_url, link_text, page_location parameters
    - Check analytics consent before sending
    - _Requirements: 17.1, 17.2, 17.3, 17.4, 17.5_
  
  - [x] 4.13 Implement file download tracking
    - Add trackFileDownload() method to EventTracker
    - Include file_type, file_name, file_extension parameters
    - Check analytics consent before sending
    - _Requirements: 18.1, 18.2, 18.3, 18.4, 18.5_
  
  - [x] 4.14 Implement JavaScript error tracking
    - Add trackError() method to EventTracker
    - Include error_type, error_message, page_url, user_agent parameters
    - Set up global error handler to capture unhandled errors
    - Check analytics consent before sending
    - _Requirements: 19.1, 19.2, 19.3, 19.4, 19.5, 19.6_
  
  - [x] 4.15 Initialize analytics in main JavaScript entry point
    - Update `resources/js/app.js` to import and initialize analytics modules
    - Initialize ConsentManager, DataLayerManager, and EventTracker
    - Show cookie banner if needed
    - Track initial page view
    - Attach event listeners for navigation, external links, and downloads
    - Set up global error handler
    - _Requirements: 8.1, 16.1, 17.1, 18.1, 19.1_


  - [x] 4.16 Add event tracking to memorial profile pages
    - Update memorial show view to call trackMemorialView()
    - Add tracking for tribute submissions
    - Add tracking for media uploads
    - _Requirements: 9.1, 14.1, 15.1_
  
  - [x] 4.17 Add event tracking to search functionality
    - Update search controller/view to call trackSearch()
    - Pass search_term and results_count parameters
    - _Requirements: 10.1_
  
  - [x] 4.18 Add event tracking to forms
    - Update contact form to call trackFormSubmit()
    - Update memorial creation form to call trackMemorialCreation()
    - Update registration form to call trackSignUp()
    - Handle both success and error cases
    - _Requirements: 11.1, 12.1, 13.1_


### Phase 5: SEO - Structured Data

- [x] 5. Implement structured data service and components
  - [x] 5.1 Create StructuredDataService class
    - Create `app/Services/SEO/StructuredDataService.php`
    - Implement generateOrganizationSchema() method
    - Implement generateWebSiteSchema() method with SearchAction
    - Implement generatePersonSchema() method for memorial profiles
    - Implement generateBreadcrumbSchema() method
    - Implement toJsonLd() to convert array to JSON-LD string
    - Implement validateSchema() for Schema.org validation
    - _Requirements: 20.1, 20.7, 21.1, 21.6, 22.1, 22.7, 23.1, 23.6_
  
  - [x] 5.2 Implement Organization schema generation
    - Include organization name, URL, logo
    - Include social media profile URLs (sameAs)
    - Include contact point with email
    - _Requirements: 20.1, 20.2, 20.3, 20.4, 20.5, 20.6_
  
  - [x] 5.3 Implement WebSite schema with SearchAction
    - Include website name and URL
    - Add SearchAction with target URL template
    - Add query-input with required name=search_term_string
    - _Requirements: 21.1, 21.2, 21.3, 21.4, 21.5_
  
  - [x] 5.4 Implement Person schema for memorial profiles
    - Include person's name, birthDate, deathDate
    - Include image URL if available
    - Include description if available
    - _Requirements: 22.1, 22.2, 22.3, 22.4, 22.5, 22.6_
  
  - [x] 5.5 Implement BreadcrumbList schema
    - Include all breadcrumb items in order
    - Add position, name, and item URL for each breadcrumb
    - _Requirements: 23.1, 23.2, 23.3, 23.4, 23.5_
  
  - [x] 5.6 Create structured data Blade component
    - Create `resources/views/components/seo/structured-data.blade.php`
    - Create component class `app/View/Components/SEO/StructuredData.php`
    - Support different schema types via component parameter
    - Output JSON-LD script tag with structured data
    - _Requirements: 20.1, 21.1, 22.1, 23.1_
  
  - [x] 5.7 Add Organization and WebSite schemas to homepage
    - Update homepage view to include structured-data component
    - Output Organization schema
    - Output WebSite schema with SearchAction
    - _Requirements: 20.1, 21.1_
  
  - [x] 5.8 Add Person schema to memorial profile pages
    - Update memorial show view to include Person structured data
    - Pass memorial data to component
    - _Requirements: 22.1_
  
  - [x] 5.9 Add BreadcrumbList schema to pages with breadcrumbs
    - Update views with breadcrumb navigation
    - Pass breadcrumb data to structured-data component
    - _Requirements: 23.1_


### Phase 6: SEO - Meta Tags & Optimization

- [x] 6. Implement meta tag service and optimization
  - [x] 6.1 Create MetaTagService class
    - Create `app/Services/SEO/MetaTagService.php`
    - Implement generateDescription() for dynamic meta descriptions
    - Implement getOgImage() to determine Open Graph image URL
    - Implement getTwitterCardTags() to generate Twitter Card meta tags
    - Implement getCanonicalUrl() to generate canonical URL
    - Implement sanitize() to clean meta content and enforce length limits
    - _Requirements: 24.1, 24.6, 25.1, 26.1, 29.1_
  
  - [x] 6.2 Implement dynamic meta description generation
    - Generate unique descriptions for each page type (home, memorial, search, contact)
    - Localize descriptions based on current locale
    - Enforce 120-160 character length constraint
    - Include person's name and dates for memorial profiles
    - Include search term for search results pages
    - _Requirements: 24.1, 24.2, 24.3, 24.4, 24.5_
  
  - [x] 6.3 Implement Open Graph image logic
    - Use memorial's primary photo for memorial profiles
    - Fall back to default memorial image when no photo exists
    - Generate og:image, og:image:width, og:image:height, og:image:alt tags
    - Ensure URLs are absolute and publicly accessible
    - _Requirements: 25.1, 25.2, 25.3, 25.4, 25.5, 25.6_
  
  - [x] 6.4 Implement Twitter Card meta tags
    - Generate twitter:card with "summary_large_image"
    - Generate twitter:title matching page title
    - Generate twitter:description matching meta description
    - Generate twitter:image matching og:image
    - Include twitter:site if Twitter handle is configured
    - _Requirements: 26.1, 26.2, 26.3, 26.4, 26.5_
  
  - [x] 6.5 Implement canonical URL generation
    - Generate canonical link tag for every page
    - Include locale prefix in URL
    - Use absolute URLs with protocol and domain
    - Exclude non-essential query parameters
    - Use lowercase for consistency
    - _Requirements: 29.1, 29.2, 29.3, 29.4, 29.5_
  
  - [x] 6.6 Create meta tags Blade component
    - Create `resources/views/components/seo/meta-tags.blade.php`
    - Create component class `app/View/Components/SEO/MetaTags.php`
    - Output meta description, OG tags, Twitter Card tags, canonical URL
    - _Requirements: 24.1, 25.1, 26.1, 29.1_
  
  - [x] 6.7 Integrate meta tags into main layout
    - Update `resources/views/layouts/app.blade.php` to include meta-tags component
    - Pass page-specific context to component
    - _Requirements: 24.1, 25.1, 26.1, 29.1_
  
  - [x] 6.8 Implement image lazy loading
    - Create image component or helper to add loading="lazy" attribute
    - Only add lazy loading to images below the fold
    - Always add width and height attributes to prevent layout shift
    - Always include alt text for accessibility
    - _Requirements: 31.1, 31.2, 31.3, 31.4_


### Phase 7: SEO - Sitemap & Technical

- [x] 7. Implement sitemap service and technical SEO
  - [x] 7.1 Create SitemapService class
    - Create `app/Services/SEO/SitemapService.php`
    - Implement generateSitemap() for locale-specific sitemaps
    - Implement generateSitemapIndex() for sitemap index file
    - Implement getPriority() to return priority for page types
    - Implement getChangeFreq() to return change frequency for page types
    - _Requirements: 27.1, 27.9, 27.10_
  
  - [x] 7.2 Implement sitemap generation logic
    - Include priority values: homepage (1.0), memorial (0.8), static (0.6)
    - Include changefreq values: memorial (weekly), static (monthly)
    - Include lastmod timestamps for all URLs
    - Generate separate sitemap files for each locale (bs, sr, hr, de, en, it)
    - _Requirements: 27.1, 27.2, 27.3, 27.4, 27.5, 27.6, 27.7, 27.8, 27.9_
  
  - [x] 7.3 Create sitemap index file
    - Generate sitemap index linking to all locale-specific sitemaps
    - _Requirements: 27.10_
  
  - [x] 7.4 Create SitemapController
    - Create `app/Http/Controllers/SitemapController.php`
    - Add index() method to return sitemap index
    - Add show() method to return locale-specific sitemap
    - Set proper XML content-type headers
    - _Requirements: 27.9, 27.10_
  
  - [x] 7.5 Add sitemap routes
    - Add routes in `routes/web.php` for /sitemap.xml and /sitemap-{locale}.xml
    - _Requirements: 27.9, 27.10_
  
  - [x] 7.6 Optimize robots.txt
    - Create or update `public/robots.txt`
    - Allow all user agents to crawl public pages
    - Disallow crawling of /admin, /account, /api paths
    - Include sitemap URL
    - Add crawl-delay if needed for specific bots
    - _Requirements: 28.1, 28.2, 28.3, 28.4, 28.5, 28.6_
  
  - [x] 7.7 Create optimized 404 page
    - Create `resources/views/errors/404.blade.php`
    - Localize content for current locale
    - Include search box
    - Include links to popular memorial profiles
    - Include link to homepage
    - Set meta robots to "noindex, nofollow"
    - Ensure HTTP 404 status code is returned
    - _Requirements: 30.1, 30.2, 30.3, 30.4, 30.5, 30.6, 30.7_


### Phase 8: Testing & Validation

- [-] 8. Implement comprehensive testing
  - [x] 8.1 Create GTMService unit tests
    - Create `tests/Unit/Services/Analytics/GTMServiceTest.php`
    - Test getContainerId() returns correct ID per environment
    - Test isEnabled() respects ANALYTICS_ENABLED flag
    - Test isDebugMode() respects ANALYTICS_DEBUG_MODE flag
    - Test getHeadScript() generates correct HTML
    - Test getBodyNoScript() generates correct HTML
    - Test getCspDirectives() returns correct CSP directives
    - _Requirements: 1.3, 1.4, 1.5, 1.8, 34.5_
  
  - [x] 8.2 Create DataLayerService unit tests
    - Create `tests/Unit/Services/Analytics/DataLayerServiceTest.php`
    - Test getInitialState() returns correct page context
    - Test getPageType() identifies page types correctly
    - Test getUserType() returns 'guest' or 'registered'
    - Test getRegion() maps locales to regions correctly
    - _Requirements: 2.2, 2.3, 2.4, 2.5_
  
  - [x] 8.3 Create StructuredDataService unit tests
    - Create `tests/Unit/Services/SEO/StructuredDataServiceTest.php`
    - Test generateOrganizationSchema() returns valid schema
    - Test generateWebSiteSchema() includes SearchAction
    - Test generatePersonSchema() includes all person properties
    - Test generateBreadcrumbSchema() includes all breadcrumb items
    - Test toJsonLd() converts array to valid JSON-LD
    - _Requirements: 20.1, 21.1, 22.1, 23.1_
  
  - [x] 8.4 Create MetaTagService unit tests
    - Create `tests/Unit/Services/SEO/MetaTagServiceTest.php`
    - Test generateDescription() creates unique descriptions per page type
    - Test description length is between 120-160 characters
    - Test getOgImage() returns correct image URL
    - Test getTwitterCardTags() returns all required tags
    - Test getCanonicalUrl() generates correct canonical URL
    - Test sanitize() removes HTML and enforces length limits
    - _Requirements: 24.1, 24.3, 25.1, 26.1, 29.1_
  
  - [x] 8.5 Create SitemapService unit tests
    - Create `tests/Unit/Services/SEO/SitemapServiceTest.php`
    - Test getPriority() returns correct values per page type
    - Test getChangeFreq() returns correct values per page type
    - Test generateSitemap() includes all required URLs
    - Test generateSitemapIndex() links to all locale sitemaps
    - _Requirements: 27.2, 27.3, 27.6, 27.7, 27.9, 27.10_
  
  - [x] 8.6 Create GTM integration feature tests
    - Create `tests/Feature/Analytics/GTMIntegrationTest.php`
    - Test GTM scripts are loaded on pages when enabled
    - Test GTM scripts are not loaded when disabled
    - Test GTM scripts are not loaded in development environment
    - Test data layer is initialized before GTM
    - Test noscript iframe is present after body tag
    - _Requirements: 1.1, 1.2, 1.5, 1.7, 34.5_
  
  - [x] 8.7 Create consent management feature tests
    - Create `tests/Feature/Analytics/ConsentManagementTest.php`
    - Test cookie banner displays on first visit
    - Test cookie banner does not display when consent is stored
    - Test consent preferences are saved to localStorage
    - Test consent expires after 12 months
    - Test GTM consent mode is updated based on user choices
    - _Requirements: 4.1, 4.8, 5.1, 5.5, 6.2, 6.3_
  
  - [x] 8.8 Create structured data feature tests
    - Create `tests/Feature/SEO/StructuredDataTest.php`
    - Test Organization schema appears on homepage
    - Test WebSite schema with SearchAction appears on homepage
    - Test Person schema appears on memorial profile pages
    - Test BreadcrumbList schema appears on pages with breadcrumbs
    - Test all schemas validate against Schema.org
    - _Requirements: 20.1, 20.7, 21.1, 21.6, 22.1, 22.7, 23.1, 23.6_
  
  - [x] 8.9 Create meta tags feature tests
    - Create `tests/Feature/SEO/MetaTagsTest.php`
    - Test meta description is present and unique per page
    - Test OG image tags are present on memorial profiles
    - Test Twitter Card tags are present
    - Test canonical URL is present on all pages
    - _Requirements: 24.1, 25.1, 26.1, 29.1_
  
  - [x] 8.10 Create sitemap feature tests
    - Create `tests/Feature/SEO/SitemapTest.php`
    - Test sitemap index is accessible at /sitemap.xml
    - Test locale-specific sitemaps are accessible
    - Test sitemaps include correct priority and changefreq values
    - Test sitemaps include lastmod timestamps
    - _Requirements: 27.1, 27.8, 27.9, 27.10_
  
  - [x] 8.11 Create performance tests
    - Test GTM script loads asynchronously
    - Test analytics overhead is less than 100ms
    - Test no layout shifts caused by analytics (CLS < 0.01)
    - Test images have lazy loading attribute
    - _Requirements: 31.1, 36.1, 36.2, 36.3, 36.4_
  
  - [x] 8.12 Create CSP compatibility tests
    - Test GTM scripts work with CSP nonce
    - Test no inline scripts without nonces
    - Test all analytics domains are in CSP directives
    - _Requirements: 37.1, 37.2, 37.3, 37.4_


### Phase 9: Documentation & Deployment Preparation

- [x] 9. Create documentation and deployment guides
  - [x] 9.1 Create GTM setup guide
    - Document GTM container creation process
    - Document GA4 configuration in GTM
    - Document consent mode setup in GTM
    - Document tag configuration for all 12 event types
    - Document testing with GTM preview mode
    - _Requirements: 1.1, 3.1, 42.1, 42.2_
  
  - [x] 9.2 Create GA4 configuration guide
    - Document GA4 property creation
    - Document enhanced measurement settings
    - Document user properties configuration
    - Document data retention settings (14 months)
    - Document IP anonymization and data sharing settings
    - Document DebugView usage for testing
    - _Requirements: 3.2, 3.3, 3.4, 40.1, 40.2, 40.3, 40.4, 43.1, 43.2_
  
  - [x] 9.3 Create event tracking reference
    - Document all 12 event types with parameter schemas
    - Document when each event is triggered
    - Document consent requirements for each event
    - Provide code examples for each event type
    - _Requirements: 8.1, 9.1, 10.1, 11.1, 12.1, 13.1, 14.1, 15.1, 16.1, 17.1, 18.1, 19.1_
  
  - [x] 9.4 Create troubleshooting guide
    - Document common issues and solutions
    - Document how to use debug mode
    - Document how to validate event tracking
    - Document how to test consent management
    - Document how to validate structured data
    - _Requirements: 35.1, 35.2, 35.3, 44.1, 44.2, 44.3_
  
  - [x] 9.5 Create deployment checklist
    - List all environment variables to configure
    - List GTM container IDs for production and staging
    - List GA4 measurement ID
    - List Google Search Console verification code
    - List social media URLs
    - Verify robots.txt is configured
    - Verify sitemap is accessible
    - Verify 404 page is working
    - Verify cookie banner displays correctly in all locales
    - Verify all event tracking is working
    - _Requirements: 1.6, 28.5, 30.1, 33.1, 34.1, 34.2, 38.1-38.6_
  
  - [x] 9.6 Create event tracking validation page (non-production only)
    - Create route accessible only in non-production environments
    - Create view listing all 12 event types
    - Add buttons to manually trigger each event
    - Display event parameters for verification
    - _Requirements: 44.1, 44.2, 44.3, 44.4_

- [x] 10. Final checkpoint - Verify implementation completeness
  - Ensure all tests pass
  - Verify GTM and GA4 are working in staging environment
  - Verify cookie consent works in all 6 locales
  - Verify all 12 event types are tracked correctly
  - Verify structured data validates on Google Rich Results Test
  - Verify sitemap is accessible and complete
  - Verify performance budget is met (< 100ms overhead)
  - Ask the user if any questions or issues arise

## Notes

- Tasks marked with `*` are optional testing tasks and can be skipped for faster MVP
- Each task references specific requirements for traceability
- The implementation uses Laravel 11 with Blade templates and supports 6 locales (bs, sr, hr, de, en, it)
- All user-facing components must be translated for all locales
- All JavaScript must be CSP-compatible with nonce support
- Performance budget: < 100ms analytics overhead, CLS < 0.01
- Cookie consent must comply with GDPR requirements
- All tracking must respect user consent choices
