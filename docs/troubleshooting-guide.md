# Troubleshooting Guide

Comprehensive troubleshooting guide for Google Analytics, GTM, cookie consent, event tracking, and SEO structured data issues.

## Table of Contents

1. [Common Issues](#common-issues)
2. [Debug Mode Usage](#debug-mode-usage)
3. [Event Validation](#event-validation)
4. [Consent Testing](#consent-testing)
5. [Structured Data Validation](#structured-data-validation)
6. [Performance Issues](#performance-issues)
7. [GTM Preview Mode Issues](#gtm-preview-mode-issues)

---

## Common Issues

### Issue 1: GTM Container Not Loading

**Symptoms:**
- GTM script not visible in page source
- No GTM-related network requests
- Events not being tracked

**Diagnosis:**

1. Check environment configuration:
```bash
# In .env file
ANALYTICS_ENABLED=true
GTM_ID=GTM-XXXXXXX  # Production
GTM_ID_STAGING=GTM-YYYYYYY  # Staging
```

2. Verify environment:
```bash
php artisan config:cache
php artisan config:clear
```

3. Check if GTM is disabled in local environment:
```php
// GTM is automatically disabled when APP_ENV=local
// Check your .env file
APP_ENV=production  # or staging
```

**Solutions:**

- ✅ Set `ANALYTICS_ENABLED=true` in `.env`
- ✅ Ensure `GTM_ID` is set for production or `GTM_ID_STAGING` for staging
- ✅ Change `APP_ENV` from `local` to `staging` or `production`
- ✅ Clear config cache: `php artisan config:clear`
- ✅ Verify GTM component is included in layout: `<x-analytics.gtm-head />`

---

### Issue 2: Events Not Appearing in GA4

**Symptoms:**
- Events fire in GTM Preview but don't appear in GA4
- GA4 Realtime report shows no events
- DebugView shows no activity

**Diagnosis:**

1. Check GTM Preview mode:
   - Are tags firing?
   - Is GA4 Configuration tag firing?
   - Are event tags firing?

2. Check consent status:
   - Is analytics consent granted?
   - Check browser console for consent logs

3. Check GA4 Measurement ID:
   - Is it correct in GTM GA4 Configuration tag?
   - Does it match your GA4 property?

**Solutions:**

- ✅ Verify Measurement ID in GTM matches GA4 property
- ✅ Ensure analytics consent is granted (check localStorage)
- ✅ Wait 24-48 hours for data to appear in standard reports (use Realtime for immediate feedback)
- ✅ Check that GA4 Configuration tag fires before event tags
- ✅ Verify no ad blockers are blocking GA4 requests

**Quick Test:**

```javascript
// In browser console
console.log(window.dataLayer);
// Should show array with events

console.log(localStorage.getItem('cookie_consent'));
// Should show consent object with analytics: true
```

---

### Issue 3: Cookie Banner Not Appearing

**Symptoms:**
- Cookie banner doesn't show on first visit
- Banner shows every time despite accepting cookies

**Diagnosis:**

1. Check localStorage:
```javascript
// In browser console
console.log(localStorage.getItem('cookie_consent'));
```

2. Check consent manager initialization:
```javascript
// Should be defined
console.log(window.consentManager);
```

3. Check banner element:
```javascript
// Should exist in DOM
console.log(document.getElementById('cookie-consent-banner'));
```

**Solutions:**

**Banner Not Showing:**
- ✅ Clear localStorage: `localStorage.clear()`
- ✅ Verify banner component is included in layout
- ✅ Check that `shouldShowBanner()` returns true
- ✅ Verify JavaScript is loaded: check for `window.consentManager`

**Banner Shows Every Time:**
- ✅ Check localStorage is working (not disabled in browser)
- ✅ Verify consent is being saved: check `saveConsent()` method
- ✅ Check consent version matches: `version: 1`
- ✅ Verify consent hasn't expired (12 months)

**Quick Fix:**

```javascript
// Test consent saving
window.consentManager.saveConsent({
  analytics: true,
  marketing: false
});

// Verify it was saved
console.log(window.consentManager.getConsent());
```

---

### Issue 4: Events Blocked by Consent

**Symptoms:**
- Events show "blocked: consent_denied" in debug logs
- No events appear in GA4 despite user activity

**Diagnosis:**

1. Check consent status:
```javascript
const consent = window.consentManager.getConsent();
console.log('Analytics consent:', consent?.analytics);
```

2. Check debug logs:
```javascript
// Enable debug mode in .env
ANALYTICS_DEBUG_MODE=true
```

3. Verify consent update was sent to GTM:
```javascript
// Check dataLayer for consent_update event
console.log(window.dataLayer.filter(item => item.event === 'consent_update'));
```

**Solutions:**

- ✅ Accept cookies via banner
- ✅ Verify consent is saved to localStorage
- ✅ Check that `updateGTMConsent()` is called after accepting
- ✅ Verify GTM consent mode tags are configured correctly
- ✅ Clear localStorage and re-accept cookies

**Manual Consent Grant:**

```javascript
// Manually grant consent for testing
window.consentManager.saveConsent({
  analytics: true,
  marketing: true
});

// Reload page
location.reload();
```

---

### Issue 5: Structured Data Not Validating

**Symptoms:**
- Google Rich Results Test shows errors
- Schema.org validator reports issues
- Structured data not appearing in search results

**Diagnosis:**

1. View page source and find JSON-LD script tags
2. Copy JSON-LD content
3. Test in validators:
   - [Google Rich Results Test](https://search.google.com/test/rich-results)
   - [Schema.org Validator](https://validator.schema.org/)

**Common Errors:**

**Missing Required Fields:**
```json
// Organization schema requires name and url
{
  "@context": "https://schema.org",
  "@type": "Organization",
  "name": "Spomenar",  // Required
  "url": "https://example.com"  // Required
}
```

**Invalid Date Format:**
```json
// Person schema dates must be YYYY-MM-DD
{
  "@type": "Person",
  "birthDate": "1950-01-15",  // Correct
  "deathDate": "2023-06-20"   // Correct
}
```

**Invalid URL:**
```json
// URLs must be absolute
{
  "@type": "Organization",
  "url": "https://example.com",  // Correct
  "logo": "https://example.com/logo.png"  // Correct, not "/logo.png"
}
```

**Solutions:**

- ✅ Verify all required fields are present
- ✅ Use absolute URLs (include protocol and domain)
- ✅ Format dates as YYYY-MM-DD
- ✅ Escape special characters in JSON
- ✅ Validate JSON syntax (no trailing commas)

---

### Issue 6: Sitemap Not Accessible

**Symptoms:**
- `/sitemap.xml` returns 404
- Sitemap doesn't include all pages
- Sitemap format is invalid

**Diagnosis:**

1. Check route is registered:
```bash
php artisan route:list | grep sitemap
```

2. Test sitemap generation:
```bash
curl https://yoursite.com/sitemap.xml
```

3. Validate XML format:
   - Use [XML Sitemap Validator](https://www.xml-sitemaps.com/validate-xml-sitemap.html)

**Solutions:**

- ✅ Verify sitemap routes are registered in `routes/web.php`
- ✅ Check SitemapController exists and is working
- ✅ Clear route cache: `php artisan route:clear`
- ✅ Verify XML content-type header is set
- ✅ Check that all URLs are absolute and valid

**Test Sitemap Generation:**

```php
// In tinker
php artisan tinker

$service = app(\App\Services\SEO\SitemapService::class);
echo $service->generateSitemap('en');
```

---

## Debug Mode Usage

### Enabling Debug Mode

Add to `.env` file:

```env
ANALYTICS_DEBUG_MODE=true
```

Clear config cache:

```bash
php artisan config:clear
```

### Debug Output

With debug mode enabled, you'll see console logs for:

**Data Layer Pushes:**
```
[Analytics Debug] Event: page_view {page_path: "/", page_title: "Home", ...}
```

**Consent Updates:**
```
[Analytics Debug] Consent updated: {analytics: true, marketing: false}
```

**Blocked Events:**
```
[Analytics Debug] Event: view_memorial {memorial_id: "123", blocked: "consent_denied"}
```

### Debug Mode Checklist

When debugging, check:

1. ✅ GTM container loads
2. ✅ Data layer initializes with page context
3. ✅ Consent manager initializes
4. ✅ Cookie banner shows (if no consent stored)
5. ✅ Events fire when actions are performed
6. ✅ Events are blocked when consent is denied
7. ✅ Events are sent when consent is granted

### Browser Console Commands

Useful commands for debugging:

```javascript
// Check if analytics is initialized
console.log(window.eventTracker);
console.log(window.consentManager);
console.log(window.dataLayerManager);

// Check data layer
console.log(window.dataLayer);

// Check consent status
console.log(window.consentManager.getConsent());

// Check if banner should show
console.log(window.consentManager.shouldShowBanner());

// Manually trigger event
window.eventTracker.trackPageView({
  page_path: '/test',
  page_title: 'Test',
  page_locale: 'en',
  page_type: 'test'
});

// Clear consent and reload
localStorage.removeItem('cookie_consent');
location.reload();
```

---

## Event Validation

### Using GTM Preview Mode

1. **Enable Preview Mode:**
   - In GTM, click **Preview**
   - Enter your site URL
   - Click **Connect**

2. **Verify Tag Firing:**
   - Perform action (e.g., view memorial)
   - Check Tag Assistant for event tag firing
   - Verify all parameters are present

3. **Check Data Layer:**
   - Click **Data Layer** tab
   - Find your event in the list
   - Verify all parameters have correct values

### Using GA4 DebugView

1. **Enable Debug Mode:**
   - Set `ANALYTICS_DEBUG_MODE=true` in `.env`
   - Or add `?debug_mode=true` to URL (if implemented)

2. **Access DebugView:**
   - Go to GA4 property
   - Navigate to **Configure** > **DebugView**

3. **Verify Events:**
   - Perform actions on your site
   - Watch for events appearing in DebugView
   - Click events to see parameters

### Event Validation Checklist

For each event type, verify:

| Check | Description |
|-------|-------------|
| ✅ Event fires | Event appears in GTM Preview and GA4 DebugView |
| ✅ All parameters present | All required parameters are included |
| ✅ Parameter values correct | Values match expected data |
| ✅ Consent respected | Event blocked when consent denied |
| ✅ No errors | No JavaScript errors in console |
| ✅ Sanitization works | Long values are truncated to 100 chars |

### Common Event Issues

**Event Not Firing:**
- Check that event tracker is initialized
- Verify consent is granted
- Check for JavaScript errors
- Verify trigger conditions are met

**Missing Parameters:**
- Check data layer variable configuration in GTM
- Verify parameter names match exactly
- Check that values are not null/undefined

**Wrong Parameter Values:**
- Check data sanitization
- Verify data types (string, number, boolean)
- Check for encoding issues

---

## Consent Testing

### Test Scenarios

#### Scenario 1: First Visit (No Consent)

1. Clear localStorage: `localStorage.clear()`
2. Reload page
3. **Expected:**
   - Cookie banner appears
   - No analytics events fire
   - GTM consent mode is "denied"

#### Scenario 2: Accept All Cookies

1. Clear localStorage
2. Reload page
3. Click "Accept All"
4. **Expected:**
   - Banner disappears
   - Consent saved to localStorage
   - `consent_update` event fires
   - Analytics events start firing
   - GTM consent mode is "granted"

#### Scenario 3: Reject All Cookies

1. Clear localStorage
2. Reload page
3. Click "Reject All"
4. **Expected:**
   - Banner disappears
   - Consent saved with analytics: false
   - No analytics events fire
   - GTM consent mode remains "denied"

#### Scenario 4: Customize Settings

1. Clear localStorage
2. Reload page
3. Click "Customize"
4. Enable analytics, disable marketing
5. Save preferences
6. **Expected:**
   - Banner disappears
   - Consent saved with analytics: true, marketing: false
   - Analytics events fire
   - Marketing tags don't fire

#### Scenario 5: Returning User

1. Accept cookies
2. Close browser
3. Return to site
4. **Expected:**
   - Banner doesn't appear
   - Consent loaded from localStorage
   - Analytics events fire immediately

#### Scenario 6: Expired Consent

1. Accept cookies
2. Manually set expiration to past:
```javascript
const consent = JSON.parse(localStorage.getItem('cookie_consent'));
consent.expiresAt = Date.now() - 1000;
localStorage.setItem('cookie_consent', JSON.stringify(consent));
```
3. Reload page
4. **Expected:**
   - Banner appears again
   - Old consent is ignored

### Consent Verification Commands

```javascript
// Get current consent
const consent = window.consentManager.getConsent();
console.log('Consent:', consent);

// Check if expired
console.log('Expired:', window.consentManager.isConsentExpired(consent));

// Check if banner should show
console.log('Show banner:', window.consentManager.shouldShowBanner());

// Manually save consent
window.consentManager.saveConsent({
  analytics: true,
  marketing: false
});

// Delete consent
window.consentManager.deleteConsent();
```

---

## Structured Data Validation

### Validation Tools

1. **Google Rich Results Test:**
   - URL: https://search.google.com/test/rich-results
   - Tests: Google-specific structured data
   - Shows: Preview of how it appears in search

2. **Schema.org Validator:**
   - URL: https://validator.schema.org/
   - Tests: Schema.org compliance
   - Shows: Detailed validation errors

3. **Google Search Console:**
   - URL: https://search.google.com/search-console
   - Tests: Live site structured data
   - Shows: Issues found by Google crawler

### Testing Process

1. **View Page Source:**
   - Right-click page > View Page Source
   - Find `<script type="application/ld+json">` tags
   - Copy JSON-LD content

2. **Validate in Tools:**
   - Paste into Rich Results Test
   - Check for errors and warnings
   - Fix any issues found

3. **Test All Schema Types:**
   - Organization (homepage)
   - WebSite with SearchAction (homepage)
   - Person (memorial profiles)
   - BreadcrumbList (pages with breadcrumbs)

### Common Validation Errors

**Error: Missing required field**
```
Solution: Add the required field to schema generation
```

**Error: Invalid URL**
```
Solution: Use absolute URLs with protocol
Before: "/logo.png"
After: "https://example.com/logo.png"
```

**Error: Invalid date format**
```
Solution: Use YYYY-MM-DD format
Before: "01/15/1950"
After: "1950-01-15"
```

**Error: Invalid JSON**
```
Solution: Check for syntax errors (trailing commas, quotes)
```

### Structured Data Checklist

| Schema Type | Location | Required Fields | Status |
|------------|----------|----------------|--------|
| Organization | Homepage | name, url | ✅ |
| WebSite | Homepage | name, url, potentialAction | ✅ |
| Person | Memorial | name | ✅ |
| BreadcrumbList | All pages | itemListElement | ✅ |

---

## Performance Issues

### Issue: Slow Page Load

**Diagnosis:**

1. Check GTM container size:
   - Open Network tab in DevTools
   - Filter by "gtm"
   - Check size of gtm.js

2. Check number of tags:
   - Too many tags can slow down page load
   - Review and remove unused tags

3. Check tag firing order:
   - Ensure tags fire asynchronously
   - Don't block page rendering

**Solutions:**

- ✅ Minimize number of tags in GTM
- ✅ Use async loading for GTM
- ✅ Defer non-critical tracking
- ✅ Use GTM's built-in tag sequencing
- ✅ Monitor Core Web Vitals

### Issue: Layout Shift (CLS)

**Diagnosis:**

1. Check if cookie banner causes layout shift
2. Use Lighthouse to measure CLS
3. Check if GTM scripts cause reflow

**Solutions:**

- ✅ Reserve space for cookie banner
- ✅ Use `position: fixed` for banner
- ✅ Load GTM asynchronously
- ✅ Avoid injecting content that shifts layout

### Performance Monitoring

```javascript
// Measure analytics overhead
const start = performance.now();
// ... analytics code ...
const end = performance.now();
console.log('Analytics overhead:', end - start, 'ms');
// Should be < 100ms
```

---

## GTM Preview Mode Issues

### Issue: Preview Mode Won't Connect

**Solutions:**

- ✅ Disable ad blockers
- ✅ Allow third-party cookies
- ✅ Use same browser for GTM and site
- ✅ Check that GTM container ID is correct
- ✅ Try incognito mode

### Issue: Tags Not Firing in Preview

**Diagnosis:**

1. Check trigger conditions
2. Verify data layer variables
3. Check tag firing order

**Solutions:**

- ✅ Review trigger configuration
- ✅ Check that data layer variables are defined
- ✅ Verify consent is granted
- ✅ Check tag firing priorities

---

## Getting Help

### Information to Provide

When asking for help, provide:

1. **Environment:**
   - APP_ENV value
   - ANALYTICS_ENABLED value
   - GTM container ID

2. **Issue Description:**
   - What you expected to happen
   - What actually happened
   - Steps to reproduce

3. **Debug Information:**
   - Browser console errors
   - Network tab screenshots
   - GTM Preview mode screenshots
   - Data layer contents

4. **What You've Tried:**
   - Solutions attempted
   - Results of each attempt

### Useful Debug Commands

```javascript
// Dump all debug info
console.log('=== Analytics Debug Info ===');
console.log('EventTracker:', window.eventTracker);
console.log('ConsentManager:', window.consentManager);
console.log('DataLayer:', window.dataLayer);
console.log('Consent:', window.consentManager?.getConsent());
console.log('GTM Loaded:', typeof google_tag_manager !== 'undefined');
console.log('GA4 Loaded:', typeof gtag !== 'undefined');
```

---

## Next Steps

- [GTM Setup Guide](./gtm-setup-guide.md) - Configure GTM container
- [GA4 Configuration Guide](./ga4-configuration-guide.md) - Set up GA4 property
- [Event Tracking Reference](./event-tracking-reference.md) - Event schemas and examples
- [Deployment Checklist](./deployment-checklist.md) - Pre-launch verification
