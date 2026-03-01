# Google Analytics & SEO Implementation Documentation

Complete documentation for the Google Analytics 4, Google Tag Manager, cookie consent, event tracking, and SEO implementation for the memorial application.

## Documentation Overview

This documentation suite provides comprehensive guides for setting up, configuring, testing, and deploying the analytics and SEO features.

### Quick Links

| Document | Purpose | Audience |
|----------|---------|----------|
| [GTM Setup Guide](./gtm-setup-guide.md) | Configure Google Tag Manager | Marketing, Developers |
| [GA4 Configuration Guide](./ga4-configuration-guide.md) | Set up Google Analytics 4 | Marketing, Analysts |
| [Event Tracking Reference](./event-tracking-reference.md) | Event schemas and implementation | Developers |
| [Troubleshooting Guide](./troubleshooting-guide.md) | Debug common issues | All |
| [Deployment Checklist](./deployment-checklist.md) | Pre-launch verification | DevOps, QA |
| [Event Tracking Validation](./event-tracking-validation.md) | Testing tool guide | QA, Developers |
| [SEO Meta Tags Usage](./seo-meta-tags-usage.md) | SEO implementation | Developers, SEO |

## Getting Started

### For Marketing Managers

Start with these guides to set up analytics:

1. **[GTM Setup Guide](./gtm-setup-guide.md)** - Create GTM containers and configure tags
2. **[GA4 Configuration Guide](./ga4-configuration-guide.md)** - Set up GA4 property and configure settings
3. **[Event Tracking Reference](./event-tracking-reference.md)** - Understand what events are tracked

### For Developers

Follow this sequence for implementation:

1. **[Event Tracking Reference](./event-tracking-reference.md)** - Understand event schemas and implementation
2. **[GTM Setup Guide](./gtm-setup-guide.md)** - Configure GTM integration
3. **[Troubleshooting Guide](./troubleshooting-guide.md)** - Debug issues during development
4. **[Event Tracking Validation](./event-tracking-validation.md)** - Use the validation page for testing

### For QA/Testing

Use these guides for testing:

1. **[Event Tracking Validation](./event-tracking-validation.md)** - Interactive testing tool
2. **[Troubleshooting Guide](./troubleshooting-guide.md)** - Debug common issues
3. **[Deployment Checklist](./deployment-checklist.md)** - Verify all features before launch

### For DevOps

Follow the deployment process:

1. **[Deployment Checklist](./deployment-checklist.md)** - Complete pre-launch verification
2. **[GTM Setup Guide](./gtm-setup-guide.md)** - Verify GTM configuration
3. **[GA4 Configuration Guide](./ga4-configuration-guide.md)** - Verify GA4 settings

## Feature Overview

### Analytics Features

- **Google Tag Manager (GTM)** - Tag management system for analytics
- **Google Analytics 4 (GA4)** - Analytics platform for tracking user behavior
- **Cookie Consent Management** - GDPR-compliant consent system
- **12 Event Types** - Comprehensive event tracking
- **Debug Mode** - Development and testing support

### SEO Features

- **Structured Data** - Schema.org JSON-LD markup
- **Meta Tags** - Dynamic meta descriptions and Open Graph tags
- **Sitemaps** - XML sitemaps for all locales
- **Robots.txt** - Search engine crawling configuration
- **404 Page** - Optimized error page

### Supported Locales

The implementation supports 6 locales:

- **bs** - Bosnian
- **sr** - Serbian
- **hr** - Croatian
- **de** - German
- **en** - English
- **it** - Italian

## Event Types

The application tracks 12 distinct event types:

| # | Event Name | Purpose |
|---|------------|---------|
| 1 | page_view | Track page navigation |
| 2 | view_memorial | Track memorial profile views |
| 3 | search | Track search queries |
| 4 | form_submit | Track form submissions |
| 5 | sign_up | Track user registrations |
| 6 | create_memorial | Track memorial creation |
| 7 | upload_media | Track media uploads |
| 8 | submit_tribute | Track tribute submissions |
| 9 | navigation_click | Track navigation clicks |
| 10 | outbound_click | Track external link clicks |
| 11 | file_download | Track file downloads |
| 12 | error_event | Track JavaScript errors |

See [Event Tracking Reference](./event-tracking-reference.md) for detailed schemas and implementation.

## Architecture

### Client-Side Components

```
Browser
├── Cookie Consent Banner (Blade + JavaScript)
├── Consent Manager (JavaScript)
├── Data Layer Manager (JavaScript)
├── Event Tracker (JavaScript)
└── GTM Container (JavaScript)
    └── GA4 Configuration
        └── Event Tags (12 types)
```

### Server-Side Components

```
Laravel Application
├── GTMService (PHP)
├── DataLayerService (PHP)
├── StructuredDataService (PHP)
├── MetaTagService (PHP)
└── SitemapService (PHP)
```

### Data Flow

```
User Action
    ↓
Event Tracker
    ↓
Consent Check → [Denied] → Event Blocked
    ↓ [Granted]
Data Layer
    ↓
GTM Container
    ↓
GA4 Property
```

## Configuration

### Environment Variables

Required environment variables:

```env
# Analytics
ANALYTICS_ENABLED=true
ANALYTICS_DEBUG_MODE=false
GTM_ID=GTM-XXXXXXX
GTM_ID_STAGING=GTM-YYYYYYY
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

See [Deployment Checklist](./deployment-checklist.md) for complete configuration guide.

## Testing

### Development Testing

1. Set `ANALYTICS_DEBUG_MODE=true` in `.env`
2. Navigate to `/analytics/validation`
3. Use the validation page to test all events
4. Check browser console for debug logs

### Staging Testing

1. Enable GTM Preview mode
2. Open GA4 DebugView
3. Test all 12 event types
4. Verify consent flow
5. Test all 6 locales

### Production Verification

1. Verify GTM container loads
2. Check cookie banner appears
3. Test event tracking after consent
4. Verify structured data
5. Check sitemap accessibility

See [Event Tracking Validation](./event-tracking-validation.md) for detailed testing guide.

## Troubleshooting

### Common Issues

| Issue | Solution |
|-------|----------|
| GTM not loading | Check `ANALYTICS_ENABLED` and `GTM_ID` in `.env` |
| Events not firing | Verify analytics consent is granted |
| Cookie banner not showing | Clear localStorage and reload |
| Structured data errors | Validate with Google Rich Results Test |
| Sitemap 404 | Check routes are registered |

See [Troubleshooting Guide](./troubleshooting-guide.md) for comprehensive solutions.

## Performance

### Performance Targets

- Analytics overhead: < 100ms
- GTM script: Async loading
- Layout shift (CLS): < 0.01
- Image lazy loading: Enabled

### Monitoring

Monitor performance using:
- Google Lighthouse
- Core Web Vitals
- GA4 performance metrics
- Browser DevTools

## Privacy & GDPR Compliance

### Compliance Features

- ✅ Cookie consent banner in all locales
- ✅ Consent storage with expiration (12 months)
- ✅ GTM consent mode integration
- ✅ Event blocking when consent denied
- ✅ Data retention: 14 months
- ✅ IP anonymization (GA4 default)
- ✅ User data deletion support
- ✅ Privacy policy integration

### User Rights

Users can:
- Accept or reject analytics cookies
- Customize cookie preferences
- Change preferences at any time
- Request data deletion

## Support

### Documentation Issues

If you find issues with the documentation:
- Check for updates in the repository
- Review related documentation
- Contact the development team

### Technical Issues

For technical support:
1. Check [Troubleshooting Guide](./troubleshooting-guide.md)
2. Review browser console for errors
3. Test in GTM Preview mode
4. Check GA4 DebugView
5. Contact development team with:
   - Environment details
   - Steps to reproduce
   - Console errors
   - Screenshots

### Analytics Questions

For analytics and reporting questions:
- Contact marketing team
- Review GA4 documentation
- Check event tracking reference

## Updates and Maintenance

### Regular Maintenance

- **Weekly:** Review event counts and data quality
- **Monthly:** Audit GTM tags and GA4 configuration
- **Quarterly:** Review structured data and SEO performance
- **Annually:** Update consent version if needed

### Version History

- **v1.0** - Initial implementation
  - GTM + GA4 integration
  - 12 event types
  - Cookie consent in 6 locales
  - Structured data
  - Sitemaps

## Additional Resources

### External Links

- [Google Tag Manager Documentation](https://support.google.com/tagmanager)
- [Google Analytics 4 Documentation](https://support.google.com/analytics)
- [Schema.org Documentation](https://schema.org/)
- [GDPR Guidelines](https://gdpr.eu/)

### Internal Links

- [Project README](../README.md)
- [Setup Guide](../SETUP.md)
- [Design System](../DESIGN_SYSTEM.md)

## License

This documentation is part of the memorial application project.

---

**Last Updated:** 2024

**Maintained By:** Development Team

**Questions?** Contact the development team or refer to the troubleshooting guide.
