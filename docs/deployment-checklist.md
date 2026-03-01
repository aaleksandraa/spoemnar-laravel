# Deployment Checklist

Complete pre-deployment checklist for Google Analytics, GTM, SEO, and cookie consent implementation. Use this checklist before launching to production.

## Table of Contents

1. [Environment Variables](#environment-variables)
2. [GTM & GA4 Configuration](#gtm--ga4-configuration)
3. [Cookie Consent](#cookie-consent)
4. [Event Tracking](#event-tracking)
5. [SEO Configuration](#seo-configuration)
6. [Technical SEO](#technical-seo)
7. [Performance](#performance)
8. [Security](#security)
9. [Testing](#testing)
10. [Post-Deployment](#post-deployment)

---

## Environment Variables

### Required Variables

Verify all required environment variables are set in production `.env`:

```env
# Application
APP_ENV=production
APP_URL=https://yoursite.com

# Analytics
ANALYTICS_ENABLED=true
ANALYTICS_DEBUG_MODE=false

# Google Tag Manager
GTM_ID=GTM-XXXXXXX
GTM_ID_STAGING=GTM-YYYYYYY

# Google Analytics 4
GA4_MEASUREMENT_ID=G-XXXXXXXXXX

# SEO
SITE_NAME="Spomenar"
SITE_URL=https://yoursite.com
GOOGLE_SEARCH_CONSOLE_VERIFICATION=xxxxxxxxxxxxx

# Social Media
FACEBOOK_URL=https://facebook.com/yourpage
TWITTER_URL=https://twitter.com/yourhandle
TWITTER_HANDLE=@yourhandle
```

### Checklist

- [ ] `APP_ENV` is set to `production`
- [ ] `APP_URL` matches production domain
- [ ] `ANALYTICS_ENABLED` is `true`
- [ ] `ANALYTICS_DEBUG_MODE` is `false`
- [ ] `GTM_ID` contains production GTM container ID
- [ ] `GTM_ID_STAGING` contains staging GTM container ID
- [ ] `GA4_MEASUREMENT_ID` is set
- [ ] `SITE_NAME` is set
- [ ] `SITE_URL` matches production domain (with https://)
- [ ] `GOOGLE_SEARCH_CONSOLE_VERIFICATION` is set
- [ ] Social media URLs are set (if applicable)

### Verification Commands

```bash
# Check environment
php artisan config:show app.env

# Check analytics config
php artisan config:show analytics

# Check SEO config
php artisan config:show seo

# Clear and cache config
php artisan config:clear
php artisan config:cache
```

---

## GTM & GA4 Configuration

### GTM Container Setup

- [ ] Production GTM container created
- [ ] Staging GTM container created
- [ ] GA4 Configuration tag created and published
- [ ] Consent Mode tags created and published
- [ ] All 12 event tags created and published
- [ ] Data Layer Variables created for all event parameters
- [ ] Custom Event triggers created for all events
- [ ] Container published with descriptive version name

### GA4 Property Setup

- [ ] GA4 property created
- [ ] Production data stream created
- [ ] Staging data stream created (optional)
- [ ] Enhanced measurement configured
- [ ] User properties created (locale, region, user_type)
- [ ] Data retention set to 14 months
- [ ] Google Signals disabled (for privacy)
- [ ] Data sharing disabled
- [ ] Custom dimensions created for event parameters

### Verification

```bash
# Test GTM loads on production
curl -I https://yoursite.com | grep -i "x-gtm"

# Check GTM in browser
# Open https://yoursite.com
# View page source
# Search for "googletagmanager.com"
```

**Browser Test:**
1. Visit production site
2. Open DevTools > Network tab
3. Filter by "gtm"
4. Verify GTM container loads
5. Check for GA4 requests after accepting cookies

---

## Cookie Consent

### Translation Files

Verify cookie consent translations exist for all 6 locales:

- [ ] `resources/lang/bs/cookies.php` (Bosnian)
- [ ] `resources/lang/sr/cookies.php` (Serbian)
- [ ] `resources/lang/hr/cookies.php` (Croatian)
- [ ] `resources/lang/de/cookies.php` (German)
- [ ] `resources/lang/en/cookies.php` (English)
- [ ] `resources/lang/it/cookies.php` (Italian)

### Cookie Banner

- [ ] Cookie banner component included in main layout
- [ ] Banner displays on first visit
- [ ] Banner doesn't display when consent is stored
- [ ] "Accept All" button works
- [ ] "Reject All" button works
- [ ] "Customize" button shows detailed settings
- [ ] Privacy policy link works
- [ ] Banner is keyboard accessible
- [ ] Banner has sufficient color contrast (WCAG AA)

### Cookie Settings Page

- [ ] Cookie settings page accessible from footer
- [ ] Route `/cookie-settings` works
- [ ] Current consent status displayed correctly
- [ ] Toggle switches work for analytics and marketing
- [ ] Cookie descriptions displayed in current locale
- [ ] Changes save without page reload
- [ ] Privacy policy link works

### Consent Functionality

- [ ] Consent saved to localStorage
- [ ] Consent includes timestamp and version
- [ ] Consent expires after 12 months
- [ ] Consent version change triggers re-consent
- [ ] GTM consent mode updates correctly
- [ ] Events blocked when consent denied
- [ ] Events fire when consent granted

### Testing Each Locale

Test cookie banner in all 6 locales:

```bash
# Test URLs
https://yoursite.com/bs
https://yoursite.com/sr
https://yoursite.com/hr
https://yoursite.com/de
https://yoursite.com/en
https://yoursite.com/it
```

For each locale:
- [ ] Banner text is translated
- [ ] Cookie category descriptions are translated
- [ ] Buttons are translated
- [ ] Privacy policy link uses correct locale

---

## Event Tracking

### Event Implementation

Verify all 12 event types are implemented:

- [ ] 1. `page_view` - Automatic on page load
- [ ] 2. `view_memorial` - Memorial profile pages
- [ ] 3. `search` - Search functionality
- [ ] 4. `form_submit` - Form submissions
- [ ] 5. `sign_up` - User registration
- [ ] 6. `create_memorial` - Memorial creation
- [ ] 7. `upload_media` - Media uploads
- [ ] 8. `submit_tribute` - Tribute submissions
- [ ] 9. `navigation_click` - Navigation links
- [ ] 10. `outbound_click` - External links
- [ ] 11. `file_download` - File downloads
- [ ] 12. `error_event` - JavaScript errors

### Event Verification

For each event type, verify:

- [ ] Event fires when expected action occurs
- [ ] All required parameters are present
- [ ] Parameter values are correct
- [ ] Event respects consent (blocked when denied)
- [ ] Event appears in GTM Preview mode
- [ ] Event appears in GA4 DebugView
- [ ] Event appears in GA4 Realtime report

### Testing Process

1. **Enable Debug Mode (Staging Only):**
```env
ANALYTICS_DEBUG_MODE=true
```

2. **Test Each Event:**
   - Perform action that triggers event
   - Check browser console for debug log
   - Verify in GTM Preview mode
   - Verify in GA4 DebugView

3. **Test Consent Blocking:**
   - Clear localStorage
   - Reject cookies
   - Perform actions
   - Verify events are blocked

4. **Test Consent Granting:**
   - Accept cookies
   - Perform actions
   - Verify events fire

---

## SEO Configuration

### Structured Data

Verify structured data is implemented:

- [ ] Organization schema on homepage
- [ ] WebSite schema with SearchAction on homepage
- [ ] Person schema on memorial profile pages
- [ ] BreadcrumbList schema on pages with breadcrumbs

### Structured Data Validation

For each schema type:

1. **View Page Source:**
   - Find `<script type="application/ld+json">` tags
   - Copy JSON-LD content

2. **Validate:**
   - Test in [Google Rich Results Test](https://search.google.com/test/rich-results)
   - Test in [Schema.org Validator](https://validator.schema.org/)
   - Fix any errors or warnings

3. **Checklist:**
   - [ ] No validation errors
   - [ ] All required fields present
   - [ ] URLs are absolute (include protocol and domain)
   - [ ] Dates in YYYY-MM-DD format
   - [ ] JSON syntax is valid

### Meta Tags

Verify meta tags are implemented:

- [ ] Unique meta description on each page type
- [ ] Meta descriptions are 120-160 characters
- [ ] Meta descriptions are localized
- [ ] Open Graph tags present (og:title, og:description, og:image)
- [ ] Twitter Card tags present
- [ ] Canonical URL on every page
- [ ] Canonical URLs are absolute
- [ ] Canonical URLs include locale prefix

### Meta Tag Verification

Test on different page types:

- [ ] Homepage
- [ ] Memorial profile page
- [ ] Search results page
- [ ] Static pages (about, contact)
- [ ] 404 page

For each page:
1. View page source
2. Check meta tags in `<head>`
3. Verify values are correct and unique

---

## Technical SEO

### Sitemap

- [ ] Sitemap index accessible at `/sitemap.xml`
- [ ] Locale-specific sitemaps accessible (e.g., `/sitemap-en.xml`)
- [ ] Sitemap includes all public pages
- [ ] Priority values set correctly (home: 1.0, memorial: 0.8, static: 0.6)
- [ ] Change frequency values set correctly
- [ ] Last modified timestamps included
- [ ] XML format is valid
- [ ] Sitemap submitted to Google Search Console

**Test Commands:**

```bash
# Test sitemap index
curl https://yoursite.com/sitemap.xml

# Test locale-specific sitemap
curl https://yoursite.com/sitemap-en.xml

# Validate XML
curl https://yoursite.com/sitemap.xml | xmllint --noout -
```

### Robots.txt

- [ ] `robots.txt` accessible at `/robots.txt`
- [ ] Allows crawling of public pages
- [ ] Disallows crawling of admin pages (`/admin`)
- [ ] Disallows crawling of user account pages (`/account`)
- [ ] Disallows crawling of API endpoints (`/api`)
- [ ] Includes sitemap URL
- [ ] Crawl-delay set if needed

**Test:**

```bash
curl https://yoursite.com/robots.txt
```

**Expected Content:**

```
User-agent: *
Allow: /
Disallow: /admin
Disallow: /account
Disallow: /api

Sitemap: https://yoursite.com/sitemap.xml
```

### 404 Page

- [ ] Custom 404 page exists
- [ ] 404 page is localized for all 6 locales
- [ ] Returns HTTP 404 status code
- [ ] Includes search box
- [ ] Includes links to popular memorials
- [ ] Includes link to homepage
- [ ] Meta robots set to "noindex, nofollow"

**Test:**

```bash
# Test 404 status
curl -I https://yoursite.com/nonexistent-page

# Should return: HTTP/1.1 404 Not Found
```

### Google Search Console

- [ ] Property created for production domain
- [ ] Verification meta tag added to homepage
- [ ] Verification successful
- [ ] Sitemap submitted
- [ ] No critical errors in coverage report

---

## Performance

### Page Load Performance

- [ ] GTM loads asynchronously
- [ ] Analytics overhead < 100ms
- [ ] No layout shifts caused by analytics (CLS < 0.01)
- [ ] Images have lazy loading attribute
- [ ] Resource hints added for external domains

**Test with Lighthouse:**

```bash
# Run Lighthouse audit
npx lighthouse https://yoursite.com --view
```

**Performance Targets:**

- [ ] Performance score > 90
- [ ] First Contentful Paint < 1.8s
- [ ] Largest Contentful Paint < 2.5s
- [ ] Cumulative Layout Shift < 0.1
- [ ] Time to Interactive < 3.8s

### Core Web Vitals

Monitor Core Web Vitals in:
- Google Search Console
- GA4 (if configured)
- Real User Monitoring tools

---

## Security

### Content Security Policy

- [ ] CSP headers configured
- [ ] GTM domains whitelisted in `script-src`
- [ ] GA4 domains whitelisted in `connect-src`
- [ ] Image domains whitelisted in `img-src`
- [ ] Nonce support implemented for inline scripts

**CSP Directives:**

```
script-src 'self' https://www.googletagmanager.com https://www.google-analytics.com;
connect-src 'self' https://www.google-analytics.com https://analytics.google.com https://stats.g.doubleclick.net;
img-src 'self' https://www.google-analytics.com https://www.googletagmanager.com;
```

### Privacy & GDPR

- [ ] Cookie consent implemented
- [ ] Privacy policy page exists and is linked
- [ ] Data retention set to 14 months
- [ ] IP anonymization enabled (GA4 default)
- [ ] Google Signals disabled
- [ ] Data sharing disabled
- [ ] User data deletion process documented

---

## Testing

### Pre-Deployment Testing

#### Staging Environment

- [ ] All features tested in staging
- [ ] GTM Preview mode tested
- [ ] GA4 DebugView tested
- [ ] All 12 events verified
- [ ] Consent flow tested
- [ ] All 6 locales tested
- [ ] Structured data validated
- [ ] Sitemap tested
- [ ] Performance tested

#### Browser Testing

Test in major browsers:

- [ ] Chrome (latest)
- [ ] Firefox (latest)
- [ ] Safari (latest)
- [ ] Edge (latest)
- [ ] Mobile Safari (iOS)
- [ ] Chrome Mobile (Android)

#### Device Testing

- [ ] Desktop (1920x1080)
- [ ] Laptop (1366x768)
- [ ] Tablet (768x1024)
- [ ] Mobile (375x667)

### Production Smoke Test

After deployment, verify:

- [ ] Site loads correctly
- [ ] GTM container loads
- [ ] Cookie banner appears on first visit
- [ ] Events fire after accepting cookies
- [ ] Structured data present in page source
- [ ] Sitemap accessible
- [ ] Robots.txt accessible
- [ ] 404 page works
- [ ] No JavaScript errors in console

---

## Post-Deployment

### Immediate Checks (First Hour)

- [ ] Visit production site
- [ ] Accept cookies
- [ ] Perform various actions
- [ ] Check GA4 Realtime report for events
- [ ] Verify no errors in browser console
- [ ] Check server logs for errors

### First Day Checks

- [ ] Review GA4 Realtime report
- [ ] Check event counts are reasonable
- [ ] Verify user properties are set
- [ ] Check for any error events
- [ ] Monitor server performance
- [ ] Check for any user reports of issues

### First Week Checks

- [ ] Review GA4 standard reports
- [ ] Check event parameter data quality
- [ ] Verify structured data in Google Search Console
- [ ] Check sitemap processing status
- [ ] Review any crawl errors
- [ ] Monitor Core Web Vitals

### Ongoing Monitoring

Set up regular monitoring:

- **Daily:**
  - Check GA4 Realtime for anomalies
  - Review error events

- **Weekly:**
  - Review event counts and trends
  - Check user engagement metrics
  - Review search queries

- **Monthly:**
  - Full analytics review
  - Structured data audit
  - Performance audit
  - SEO ranking check

---

## Rollback Plan

If issues are discovered after deployment:

### Quick Fixes

**Disable Analytics:**
```env
ANALYTICS_ENABLED=false
```
```bash
php artisan config:clear
php artisan config:cache
```

**Disable Specific Events:**
- Pause tags in GTM
- Publish container

### Full Rollback

If major issues occur:

1. Revert to previous deployment
2. Investigate issues in staging
3. Fix and re-test
4. Re-deploy when ready

---

## Sign-Off Checklist

Before marking deployment as complete:

- [ ] All environment variables configured
- [ ] GTM container published
- [ ] GA4 property configured
- [ ] Cookie consent working in all locales
- [ ] All 12 events verified
- [ ] Structured data validated
- [ ] Sitemap accessible and submitted
- [ ] Robots.txt configured
- [ ] 404 page working
- [ ] Performance targets met
- [ ] Security measures in place
- [ ] All tests passed
- [ ] Production smoke test completed
- [ ] Monitoring set up
- [ ] Documentation updated
- [ ] Team trained on new features

**Deployment Approved By:**

- [ ] Developer: _______________
- [ ] QA: _______________
- [ ] Product Manager: _______________
- [ ] Marketing Manager: _______________

**Deployment Date:** _______________

---

## Additional Resources

- [GTM Setup Guide](./gtm-setup-guide.md)
- [GA4 Configuration Guide](./ga4-configuration-guide.md)
- [Event Tracking Reference](./event-tracking-reference.md)
- [Troubleshooting Guide](./troubleshooting-guide.md)
- [Event Tracking Validation Page](./event-tracking-validation.md)

---

## Support Contacts

**Technical Issues:**
- Developer: [contact info]
- DevOps: [contact info]

**Analytics Issues:**
- Marketing Manager: [contact info]
- Analytics Specialist: [contact info]

**SEO Issues:**
- SEO Manager: [contact info]

**Emergency Contact:**
- On-call: [contact info]
