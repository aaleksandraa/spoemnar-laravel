# Google Analytics 4 Configuration Guide

This guide covers setting up your Google Analytics 4 (GA4) property with proper configuration for the memorial application, including enhanced measurement, user properties, data retention, IP anonymization, and DebugView testing.

## Table of Contents

1. [GA4 Property Creation](#ga4-property-creation)
2. [Enhanced Measurement Configuration](#enhanced-measurement-configuration)
3. [User Properties Setup](#user-properties-setup)
4. [Data Retention Settings](#data-retention-settings)
5. [IP Anonymization and Privacy](#ip-anonymization-and-privacy)
6. [DebugView Testing](#debugview-testing)

---

## GA4 Property Creation

### Step 1: Create GA4 Property

1. Go to [Google Analytics](https://analytics.google.com/)
2. Click **Admin** (bottom left)
3. In the **Account** column, select your account or create a new one
4. In the **Property** column, click **Create Property**
5. Fill in property details:
   - **Property name**: `Spomenar` (or your site name)
   - **Reporting time zone**: Select your timezone
   - **Currency**: Select your currency
6. Click **Next**

### Step 2: Configure Business Information

1. **Industry category**: Select appropriate category (e.g., "Community & Non-Profit")
2. **Business size**: Select your organization size
3. **Business objectives**: Select relevant objectives:
   - Generate leads
   - Examine user behavior
   - Measure customer engagement
4. Click **Create**
5. Accept the Terms of Service

### Step 3: Set Up Data Stream

1. Select **Web** as your platform
2. Fill in data stream details:
   - **Website URL**: Your production URL (e.g., `https://example.com`)
   - **Stream name**: `Spomenar Production Web`
3. Click **Create stream**

### Step 4: Get Measurement ID

After creating the data stream, you'll see your **Measurement ID** (format: `G-XXXXXXXXXX`).

Add this to your `.env` file:

```env
GA4_MEASUREMENT_ID=G-XXXXXXXXXX
```

### Step 5: Create Staging Data Stream (Optional)

For testing purposes, create a separate data stream for staging:

1. In **Admin** > **Data Streams**, click **Add stream**
2. Select **Web**
3. **Website URL**: Your staging URL
4. **Stream name**: `Spomenar Staging Web`
5. Click **Create stream**
6. Note the staging Measurement ID

---

## Enhanced Measurement Configuration

Enhanced measurement automatically tracks common user interactions without additional code.

### Step 1: Access Enhanced Measurement Settings

1. Go to **Admin** > **Data Streams**
2. Click on your web data stream
3. Scroll down to **Enhanced measurement**
4. Click the gear icon to configure

### Step 2: Configure Enhanced Measurement Events

Enable the following enhanced measurement events:

| Event | Status | Description |
|-------|--------|-------------|
| **Page views** | ✅ Enabled | Automatically tracks page views |
| **Scrolls** | ✅ Enabled | Tracks when users scroll to bottom (90%) |
| **Outbound clicks** | ✅ Enabled | Tracks clicks to external domains |
| **Site search** | ✅ Enabled | Tracks internal site searches |
| **Video engagement** | ❌ Disabled | Not needed for this application |
| **File downloads** | ✅ Enabled | Tracks PDF, document downloads |
| **Form interactions** | ❌ Disabled | We track forms manually with more detail |

### Step 3: Configure Site Search

1. In Enhanced measurement settings, click **Show advanced settings**
2. Under **Site search**, configure:
   - **Search term query parameter**: `q`
   - This matches your search URL format: `/search?q=term`
3. Click **Save**

### Step 4: Verify Enhanced Measurement

1. Visit your site
2. Perform actions (scroll, click external links, search)
3. Go to **Reports** > **Realtime**
4. Verify enhanced measurement events appear

---

## User Properties Setup

User properties allow you to segment users based on custom attributes.

### Step 1: Create Custom User Properties

1. Go to **Admin** > **Data display** > **Custom definitions**
2. Click **Create custom dimensions**
3. Create the following user-scoped dimensions:

#### User Property 1: Locale

- **Dimension name**: `Locale`
- **Scope**: User
- **Description**: User's preferred language/locale
- **User property**: `locale`
- Click **Save**

#### User Property 2: Region

- **Dimension name**: `Region`
- **Scope**: User
- **Description**: User's geographic region
- **User property**: `region`
- Click **Save**

#### User Property 3: User Type

- **Dimension name**: `User Type`
- **Scope**: User
- **Description**: Whether user is guest or registered
- **User property**: `user_type`
- Click **Save**

### Step 2: Verify User Properties in GTM

Ensure your GTM GA4 Configuration tag includes these user properties:

```
user_properties.locale: {{DLV - locale}}
user_properties.region: {{DLV - region}}
user_properties.user_type: {{DLV - user_type}}
```

### Step 3: Test User Properties

1. Visit your site with GTM Preview mode enabled
2. Check that user properties are set in the GA4 Configuration tag
3. Go to GA4 **DebugView**
4. Click on a user session
5. Verify user properties appear in the user details

---

## Data Retention Settings

Configure data retention to comply with GDPR and privacy regulations.

### Step 1: Access Data Retention Settings

1. Go to **Admin** > **Data Settings** > **Data Retention**

### Step 2: Configure Retention Period

1. **Event data retention**: Select **14 months**
   - This is the recommended setting for GDPR compliance
   - Balances data availability with privacy requirements
2. **Reset user data on new activity**: Toggle **On**
   - This extends retention for active users
   - Inactive users' data expires after 14 months
3. Click **Save**

### Step 3: Configure User Data Deletion

1. Go to **Admin** > **Data Settings** > **Data Deletion Requests**
2. Review the process for handling user data deletion requests
3. Document the process for your team:
   - Users can request data deletion via privacy policy page
   - Requests are processed through GA4 admin interface
   - Deletion takes up to 60 days to complete

### Step 4: Document Retention Policy

Update your privacy policy to reflect:
- Data is retained for 14 months
- Users can request data deletion
- Data is automatically deleted after retention period

---

## IP Anonymization and Privacy

Configure privacy settings to protect user data and comply with regulations.

### Step 1: IP Anonymization

**Good news**: GA4 automatically anonymizes IP addresses by default. No configuration needed.

- GA4 does not log or store full IP addresses
- IP addresses are used only for geolocation, then discarded
- This is GDPR-compliant by default

### Step 2: Disable Google Signals

Google Signals uses data from users signed into Google accounts. For maximum privacy:

1. Go to **Admin** > **Data Settings** > **Data Collection**
2. Under **Google signals data collection**, click to expand
3. Toggle **Off** if you want maximum privacy
4. Click **Save**

**Recommendation**: Keep it **Off** for memorial application to respect user privacy.

### Step 3: Disable Data Sharing

Prevent Google from using your data for their purposes:

1. Go to **Admin** > **Account Settings** > **Account Data Sharing Settings**
2. Review and disable the following:
   - ❌ Google products & services
   - ❌ Benchmarking
   - ❌ Technical support
   - ✅ Account specialists (keep enabled for support)
3. Click **Save**

### Step 4: Configure Consent Mode

Ensure consent mode is properly configured in GTM (see GTM Setup Guide):

- Default consent state: `denied` for analytics_storage
- Update consent based on user choice
- Only send data when consent is granted

### Step 5: Enable User Deletion

1. Go to **Admin** > **Data Settings** > **Data Deletion Requests**
2. Ensure you can process deletion requests
3. Document the process:
   - User submits request via contact form
   - Admin processes request in GA4
   - Confirmation sent to user

---

## DebugView Testing

DebugView allows you to see events in real-time during development and testing.

### Step 1: Enable Debug Mode

Add this to your `.env` file for staging/development:

```env
ANALYTICS_DEBUG_MODE=true
```

This enables debug logging in the browser console.

### Step 2: Access DebugView

1. Go to **Admin** > **DebugView**
2. Or navigate to **Configure** > **DebugView**

### Step 3: Test Event Tracking

1. Visit your staging site
2. Perform various actions:
   - Navigate pages
   - View memorial profiles
   - Submit forms
   - Upload media
   - Click navigation links
3. Watch DebugView for events appearing in real-time

### Step 4: Verify Event Parameters

For each event in DebugView:

1. Click on the event name
2. Verify all parameters are present and correct
3. Check parameter values match expected data

Example for `view_memorial` event:
- ✅ `memorial_id`: Present and correct
- ✅ `memorial_slug`: Present and correct
- ✅ `locale`: Matches current locale
- ✅ `is_public`: Boolean value

### Step 5: Test Consent Flow

1. Clear localStorage: `localStorage.clear()`
2. Refresh the page
3. In DebugView, verify:
   - No events fire initially (consent denied)
4. Accept cookies
5. Verify:
   - `consent_update` event fires
   - Subsequent events start appearing

### Step 6: Check User Properties

1. In DebugView, click on a user session
2. Verify user properties are set:
   - `locale`: Current locale (bs, sr, hr, de, en, it)
   - `region`: Mapped region (BA, RS, HR, DE, US, IT)
   - `user_type`: guest or registered

### Step 7: Validate Event Schemas

For each of the 12 event types, verify:

| Event | Required Parameters | Status |
|-------|-------------------|--------|
| page_view | page_path, page_title, page_locale, page_type | ✅ |
| view_memorial | memorial_id, memorial_slug, locale, is_public | ✅ |
| search | search_term, results_count, locale | ✅ |
| form_submit | form_type, locale, success, error_type | ✅ |
| sign_up | locale, registration_method | ✅ |
| create_memorial | locale, is_public | ✅ |
| upload_media | media_type, memorial_id, file_size_kb | ✅ |
| submit_tribute | memorial_id, locale, tribute_type | ✅ |
| navigation_click | menu_item, destination_url, locale | ✅ |
| outbound_click | link_url, link_text, page_location | ✅ |
| file_download | file_type, file_name, file_extension | ✅ |
| error_event | error_type, error_message, page_url, user_agent | ✅ |

---

## Creating Custom Reports

### Step 1: Create Exploration Report

1. Go to **Explore** (left sidebar)
2. Click **Blank** to create a new exploration
3. **Name**: `Memorial Engagement Analysis`

### Step 2: Configure Dimensions

Add these dimensions:
- Event name
- Page path
- Locale
- User type
- Memorial ID (custom parameter)

### Step 3: Configure Metrics

Add these metrics:
- Event count
- Total users
- Sessions
- Engagement rate

### Step 4: Create Visualizations

Create reports for:
- **Most Viewed Memorials**: view_memorial events by memorial_id
- **Search Queries**: search events by search_term
- **Form Conversions**: form_submit events by form_type and success
- **User Engagement by Locale**: Events by locale

---

## Monitoring and Alerts

### Step 1: Set Up Custom Alerts

1. Go to **Admin** > **Custom Alerts** (if available in your GA4 version)
2. Create alerts for:
   - Sudden drop in page views (> 50% decrease)
   - Spike in error events (> 10 errors per hour)
   - Form submission failures (success rate < 80%)

### Step 2: Regular Monitoring

Schedule regular checks:
- **Daily**: Review Realtime report for anomalies
- **Weekly**: Check event counts and user engagement
- **Monthly**: Review custom reports and user properties distribution

---

## Best Practices

1. **Test Before Production**: Always test in staging with DebugView
2. **Monitor Data Quality**: Regularly check that events have all required parameters
3. **Respect Privacy**: Keep data sharing disabled and honor user consent
4. **Document Changes**: Keep notes on configuration changes
5. **Regular Audits**: Review custom dimensions and events quarterly
6. **GDPR Compliance**: Ensure 14-month retention and user deletion process
7. **Performance**: Monitor that tracking doesn't slow down your site
8. **Consent First**: Never track without user consent

---

## Troubleshooting

### Events Not Appearing in GA4

1. Check GTM Preview mode - are tags firing?
2. Verify Measurement ID is correct in GTM
3. Check consent is granted
4. Wait 24-48 hours for data to appear in standard reports (use Realtime for immediate feedback)

### User Properties Not Showing

1. Verify custom dimensions are created in GA4
2. Check GTM GA4 Configuration tag includes user properties
3. Wait 24-48 hours for user properties to populate

### DebugView Not Showing Events

1. Verify you're on the correct GA4 property
2. Check that debug mode is enabled
3. Clear browser cache and cookies
4. Try in incognito mode

---

## Next Steps

- [GTM Setup Guide](./gtm-setup-guide.md) - Configure GTM container
- [Event Tracking Reference](./event-tracking-reference.md) - Detailed event schemas
- [Troubleshooting Guide](./troubleshooting-guide.md) - Common issues and solutions
- [Deployment Checklist](./deployment-checklist.md) - Pre-launch verification
