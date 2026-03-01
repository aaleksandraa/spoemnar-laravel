# Google Tag Manager Setup Guide

This guide walks you through setting up Google Tag Manager (GTM) for the memorial application, including container creation, GA4 configuration, consent mode implementation, and tag configuration for all 12 event types.

## Table of Contents

1. [GTM Container Creation](#gtm-container-creation)
2. [GA4 Configuration in GTM](#ga4-configuration-in-gtm)
3. [Consent Mode Setup](#consent-mode-setup)
4. [Tag Configuration for Event Types](#tag-configuration-for-event-types)
5. [Testing with Preview Mode](#testing-with-preview-mode)

---

## GTM Container Creation

### Step 1: Create GTM Account and Container

1. Go to [Google Tag Manager](https://tagmanager.google.com/)
2. Click **Create Account**
3. Fill in account details:
   - **Account Name**: Your organization name
   - **Country**: Select your country
4. Set up container:
   - **Container Name**: `Spomenar Production` (or your site name)
   - **Target Platform**: Web
5. Click **Create** and accept the Terms of Service

### Step 2: Create Staging Container

For testing purposes, create a separate staging container:

1. In GTM, click **Admin** > **Create Container**
2. **Container Name**: `Spomenar Staging`
3. **Target Platform**: Web
4. Click **Create**

### Step 3: Get Container IDs

After creating containers, note down the container IDs:
- Production: `GTM-XXXXXXX`
- Staging: `GTM-YYYYYYY`

Add these to your `.env` file:

```env
GTM_ID=GTM-XXXXXXX
GTM_ID_STAGING=GTM-YYYYYYY
ANALYTICS_ENABLED=true
```

---

## GA4 Configuration in GTM

### Step 1: Create GA4 Configuration Tag

1. In GTM, go to **Tags** > **New**
2. **Tag Configuration**:
   - Click tag configuration area
   - Select **Google Analytics: GA4 Configuration**
3. **Configuration**:
   - **Measurement ID**: Enter your GA4 measurement ID (G-XXXXXXXXXX)
   - **Configuration Settings**: Add the following fields:
     - `send_page_view`: `false` (we'll handle this manually)
4. **Triggering**:
   - Select **Consent Initialization - All Pages**
5. **Tag Name**: `GA4 Configuration`
6. Click **Save**

### Step 2: Configure User Properties

In the GA4 Configuration tag, add custom user properties:

1. Edit the **GA4 Configuration** tag
2. Under **Fields to Set**, add:
   - `user_properties.locale`: `{{DLV - locale}}`
   - `user_properties.region`: `{{DLV - region}}`
   - `user_properties.user_type`: `{{DLV - user_type}}`
3. Click **Save**

### Step 3: Create Data Layer Variables

Create variables to capture data layer values:

1. Go to **Variables** > **User-Defined Variables** > **New**
2. Create the following Data Layer Variables:

| Variable Name | Data Layer Variable Name |
|--------------|-------------------------|
| DLV - locale | locale |
| DLV - region | region |
| DLV - user_type | user_type |
| DLV - page_type | page_type |
| DLV - page_path | page_path |
| DLV - page_title | page_title |

For each variable:
- **Variable Type**: Data Layer Variable
- **Data Layer Variable Name**: (as shown in table)
- **Data Layer Version**: Version 2

---

## Consent Mode Setup

### Step 1: Create Consent Initialization Trigger

1. Go to **Triggers** > **New**
2. **Trigger Configuration**: Consent Initialization - All Pages
3. **Trigger Name**: `Consent Initialization - All Pages`
4. Click **Save**

### Step 2: Configure Default Consent State

1. Go to **Tags** > **New**
2. **Tag Configuration**: Custom HTML
3. **HTML**:

```html
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  
  gtag('consent', 'default', {
    'analytics_storage': 'denied',
    'ad_storage': 'denied',
    'functionality_storage': 'granted',
    'personalization_storage': 'denied',
    'security_storage': 'granted',
    'wait_for_update': 500
  });
</script>
```

4. **Triggering**: Consent Initialization - All Pages
5. **Tag Name**: `Consent Mode - Default State`
6. **Advanced Settings** > **Tag firing options**: Once per page
7. Click **Save**

### Step 3: Create Consent Update Trigger

1. Go to **Triggers** > **New**
2. **Trigger Type**: Custom Event
3. **Event name**: `consent_update`
4. **Trigger Name**: `Custom Event - consent_update`
5. Click **Save**

### Step 4: Create Consent Update Tag

1. Go to **Tags** > **New**
2. **Tag Configuration**: Custom HTML
3. **HTML**:

```html
<script>
  gtag('consent', 'update', {
    'analytics_storage': {{consent.analytics_storage}},
    'ad_storage': {{consent.ad_storage}},
    'functionality_storage': {{consent.functionality_storage}},
    'personalization_storage': {{consent.personalization_storage}},
    'security_storage': {{consent.security_storage}}
  });
</script>
```

4. **Triggering**: Custom Event - consent_update
5. **Tag Name**: `Consent Mode - Update`
6. Click **Save**

### Step 5: Create Consent Variables

Create Data Layer Variables for consent values:

1. Go to **Variables** > **New**
2. Create these variables:

| Variable Name | Data Layer Variable Name |
|--------------|-------------------------|
| consent.analytics_storage | consent.analytics_storage |
| consent.ad_storage | consent.ad_storage |
| consent.functionality_storage | consent.functionality_storage |
| consent.personalization_storage | consent.personalization_storage |
| consent.security_storage | consent.security_storage |

---

## Tag Configuration for Event Types

### Overview of Event Types

The application tracks 12 distinct event types:

1. page_view
2. view_memorial
3. search
4. form_submit
5. sign_up
6. create_memorial
7. upload_media
8. submit_tribute
9. navigation_click
10. outbound_click
11. file_download
12. error_event

### General Tag Setup Pattern

For each event type, follow this pattern:

1. **Create Custom Event Trigger**
2. **Create Data Layer Variables** for event parameters
3. **Create GA4 Event Tag**

### Example: Page View Event

#### Step 1: Create Trigger

1. Go to **Triggers** > **New**
2. **Trigger Type**: Custom Event
3. **Event name**: `page_view`
4. **Trigger Name**: `Custom Event - page_view`
5. Click **Save**

#### Step 2: Create Variables

Create Data Layer Variables for page_view parameters:

| Variable Name | Data Layer Variable Name |
|--------------|-------------------------|
| Event - page_path | page_path |
| Event - page_title | page_title |
| Event - page_locale | page_locale |
| Event - page_type | page_type |

#### Step 3: Create GA4 Event Tag

1. Go to **Tags** > **New**
2. **Tag Configuration**: Google Analytics: GA4 Event
3. **Configuration Tag**: Select `GA4 Configuration`
4. **Event Name**: `page_view`
5. **Event Parameters**:
   - `page_path`: `{{Event - page_path}}`
   - `page_title`: `{{Event - page_title}}`
   - `page_locale`: `{{Event - page_locale}}`
   - `page_type`: `{{Event - page_type}}`
6. **Triggering**: Custom Event - page_view
7. **Tag Name**: `GA4 Event - page_view`
8. Click **Save**

### Complete Event Configuration Reference

Repeat the above pattern for all 12 events. Here's a quick reference:

| Event Name | Parameters | Trigger Event Name |
|-----------|-----------|-------------------|
| page_view | page_path, page_title, page_locale, page_type | page_view |
| view_memorial | memorial_id, memorial_slug, locale, is_public | view_memorial |
| search | search_term, results_count, locale | search |
| form_submit | form_type, locale, success, error_type | form_submit |
| sign_up | locale, registration_method | sign_up |
| create_memorial | locale, is_public | create_memorial |
| upload_media | media_type, memorial_id, file_size_kb | upload_media |
| submit_tribute | memorial_id, locale, tribute_type | submit_tribute |
| navigation_click | menu_item, destination_url, locale | navigation_click |
| outbound_click | link_url, link_text, page_location | outbound_click |
| file_download | file_type, file_name, file_extension | file_download |
| error_event | error_type, error_message, page_url, user_agent | error_event |

**Note**: For each event, create:
1. Custom Event trigger with the event name
2. Data Layer Variables for all parameters
3. GA4 Event tag with the configuration tag and parameters

---

## Testing with Preview Mode

### Step 1: Enable Preview Mode

1. In GTM, click **Preview** button (top right)
2. Enter your staging site URL
3. Click **Connect**
4. A new window opens with Tag Assistant connected

### Step 2: Verify Tag Firing

1. Navigate through your site
2. In Tag Assistant, verify:
   - **Consent Mode - Default State** fires on page load
   - **GA4 Configuration** fires after consent initialization
   - Event tags fire when actions are performed

### Step 3: Check Data Layer

1. In Tag Assistant, click **Data Layer** tab
2. Verify data layer contains:
   - Initial page context (locale, region, user_type, page_type)
   - Event data when events are triggered
   - Consent updates when user accepts/rejects cookies

### Step 4: Test Consent Flow

1. Clear localStorage: `localStorage.clear()`
2. Refresh the page
3. Cookie banner should appear
4. Click **Accept All**
5. Verify in Tag Assistant:
   - `consent_update` event fires
   - `analytics_storage` changes to `granted`
   - GA4 events start firing

### Step 5: Verify in GA4 DebugView

1. Go to GA4 property
2. Navigate to **Configure** > **DebugView**
3. Perform actions on your site
4. Verify events appear in DebugView with correct parameters

### Common Issues and Solutions

**Issue**: Tags not firing
- **Solution**: Check that triggers are configured correctly and consent is granted

**Issue**: Data Layer variables are undefined
- **Solution**: Verify variable names match exactly with data layer keys

**Issue**: Consent mode not working
- **Solution**: Ensure consent initialization tag fires before GA4 configuration tag

**Issue**: Events missing parameters
- **Solution**: Check that Data Layer Variables are created and referenced in event tags

---

## Publishing Your Container

### Step 1: Submit Changes

1. Click **Submit** button (top right)
2. **Version Name**: `Initial Setup - GA4 + Consent Mode + 12 Events`
3. **Version Description**: Describe what was configured
4. Click **Publish**

### Step 2: Verify Production

1. Visit your production site
2. Open browser console
3. Check for GTM container load
4. Verify events are being sent to GA4

### Step 3: Monitor in GA4 Realtime

1. Go to GA4 property
2. Navigate to **Reports** > **Realtime**
3. Perform actions on your site
4. Verify events appear in realtime report

---

## Best Practices

1. **Use Descriptive Names**: Name tags, triggers, and variables clearly
2. **Test Before Publishing**: Always use Preview mode before publishing
3. **Version Control**: Use meaningful version names and descriptions
4. **Separate Environments**: Use different containers for staging and production
5. **Document Changes**: Keep notes on what each version changes
6. **Regular Audits**: Periodically review tags to remove unused ones
7. **Consent First**: Always respect user consent choices
8. **Monitor Performance**: Check that GTM doesn't slow down your site

---

## Next Steps

- [GA4 Configuration Guide](./ga4-configuration-guide.md) - Set up your GA4 property
- [Event Tracking Reference](./event-tracking-reference.md) - Detailed event schemas
- [Troubleshooting Guide](./troubleshooting-guide.md) - Common issues and solutions
