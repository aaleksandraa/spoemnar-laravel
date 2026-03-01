# Event Tracking Validation Page

The Event Tracking Validation page is a testing tool for validating all 12 event types in non-production environments. It provides an interactive interface to trigger events, view parameters, check consent status, and monitor the data layer.

## Access

**URL:** `/analytics/validation`

**Availability:** Non-production environments only (staging, development)

**Production:** Returns 404 error

## Features

### 1. Consent Management

The page displays current consent status and provides buttons to:

- **Grant Analytics Consent** - Enables event tracking
- **Revoke Analytics Consent** - Blocks event tracking
- **Clear Consent** - Removes consent from localStorage

### 2. Event Testing

For each of the 12 event types, the page provides:

- **Event Name** - The event identifier
- **Description** - What the event tracks
- **Parameters** - All parameters with example values
- **Trigger Button** - Manually fire the event
- **Event Log** - Confirmation when event is triggered

### 3. Data Layer Viewer

Real-time viewer of the data layer contents:

- **Refresh Data Layer** - Update the view with current data layer
- **Clear View** - Clear the display
- **JSON Display** - Formatted JSON of all data layer events

### 4. Testing Instructions

Built-in instructions for:

- Setting up browser DevTools
- Enabling GTM Preview mode
- Accessing GA4 DebugView
- Verifying events in multiple tools
- Testing consent blocking

## Event Types

The validation page includes all 12 event types:

1. **page_view** - Page navigation tracking
2. **view_memorial** - Memorial profile views
3. **search** - Search queries
4. **form_submit** - Form submissions
5. **sign_up** - User registrations
6. **create_memorial** - Memorial creation
7. **upload_media** - Media uploads
8. **submit_tribute** - Tribute submissions
9. **navigation_click** - Navigation link clicks
10. **outbound_click** - External link clicks
11. **file_download** - File downloads
12. **error_event** - JavaScript errors

## Usage Guide

### Step 1: Access the Page

Navigate to the validation page in your staging or development environment:

```
https://staging.yoursite.com/analytics/validation
```

### Step 2: Check Environment

Verify you're in the correct environment:

- Environment indicator shows "staging" or "local"
- Debug mode status is displayed
- Page is accessible (not 404)

### Step 3: Grant Consent

Before testing events:

1. Check current consent status
2. Click "Grant Analytics Consent"
3. Verify status changes to "Granted"

### Step 4: Set Up Testing Tools

Open the following tools:

**Browser DevTools:**
1. Press F12 or right-click > Inspect
2. Go to Console tab
3. Keep it open to see debug logs

**GTM Preview Mode:**
1. Go to GTM container
2. Click "Preview" button
3. Enter your staging URL
4. Click "Connect"

**GA4 DebugView:**
1. Go to GA4 property
2. Navigate to Configure > DebugView
3. Keep it open in another tab

### Step 5: Test Events

For each event type:

1. Click the "Trigger Event" button
2. Check for success message
3. Verify in browser console (if debug mode enabled)
4. Verify in GTM Preview mode
5. Verify in GA4 DebugView
6. Check Data Layer Viewer

### Step 6: Test Consent Blocking

Test that events respect consent:

1. Click "Revoke Analytics Consent"
2. Trigger an event
3. Check browser console for "consent_denied" message
4. Verify event doesn't appear in GA4
5. Grant consent again
6. Verify events start firing

### Step 7: Review Data Layer

Use the Data Layer Viewer to:

1. See all events pushed to data layer
2. Verify event parameters
3. Check consent updates
4. Monitor data layer state

## Verification Checklist

Use this checklist to verify each event:

- [ ] Event fires when button is clicked
- [ ] Success message appears
- [ ] Event appears in browser console (debug mode)
- [ ] Event appears in GTM Preview mode
- [ ] All parameters are present in GTM
- [ ] Event appears in GA4 DebugView
- [ ] All parameters are present in GA4
- [ ] Event is blocked when consent is denied
- [ ] Event fires when consent is granted
- [ ] Data Layer Viewer shows the event

## Troubleshooting

### Issue: EventTracker Not Initialized

**Symptoms:**
- Alert: "EventTracker not initialized"
- Events don't fire

**Solutions:**
- Check that analytics JavaScript is loaded
- Verify `resources/js/app.js` includes analytics initialization
- Check browser console for JavaScript errors
- Ensure `ANALYTICS_ENABLED=true` in `.env`

### Issue: ConsentManager Not Initialized

**Symptoms:**
- Consent status shows "ConsentManager not initialized"
- Consent buttons don't work

**Solutions:**
- Check that consent manager JavaScript is loaded
- Verify `resources/js/app.js` includes consent manager initialization
- Check browser console for JavaScript errors

### Issue: Events Not Appearing in GTM

**Symptoms:**
- Events fire but don't appear in GTM Preview mode
- Tags don't fire

**Solutions:**
- Verify GTM container is loaded (check Network tab)
- Check that GTM container ID is correct
- Verify triggers are configured in GTM
- Check that consent is granted
- Disable ad blockers

### Issue: Events Not Appearing in GA4

**Symptoms:**
- Events appear in GTM but not in GA4 DebugView
- No events in GA4 Realtime

**Solutions:**
- Verify GA4 Measurement ID is correct in GTM
- Check that GA4 Configuration tag fires
- Verify consent is granted
- Wait a few seconds for events to appear
- Check GA4 property is correct

### Issue: Data Layer Empty

**Symptoms:**
- Data Layer Viewer shows empty array or "dataLayer not found"

**Solutions:**
- Verify GTM is loaded
- Check that data layer initialization happens before GTM
- Refresh the page
- Check browser console for errors

## Best Practices

1. **Test in Staging First** - Always test in staging before production
2. **Use Debug Mode** - Enable debug mode for detailed logging
3. **Test All Events** - Verify all 12 event types work correctly
4. **Test Consent Flow** - Verify events respect consent choices
5. **Check All Tools** - Verify events in console, GTM, and GA4
6. **Document Issues** - Keep notes on any issues found
7. **Regular Testing** - Test after any analytics changes

## Security Notes

- **Non-Production Only** - Page returns 404 in production
- **No Sensitive Data** - Example parameters don't contain real user data
- **Testing Only** - Events triggered are for testing purposes
- **Consent Required** - Events still respect consent even in testing

## Integration with Other Tools

### GTM Preview Mode

The validation page works seamlessly with GTM Preview mode:

1. Start GTM Preview mode
2. Navigate to validation page
3. Trigger events
4. See events in Tag Assistant

### GA4 DebugView

Events triggered on the validation page appear in GA4 DebugView:

1. Open GA4 DebugView
2. Navigate to validation page
3. Trigger events
4. See events in DebugView with all parameters

### Browser DevTools

Use DevTools Console to see debug logs:

1. Enable debug mode: `ANALYTICS_DEBUG_MODE=true`
2. Open Console tab
3. Trigger events
4. See detailed logs with parameters

## Example Testing Session

Here's a complete testing session workflow:

```
1. Navigate to /analytics/validation
2. Verify environment is staging
3. Open DevTools Console
4. Start GTM Preview mode
5. Open GA4 DebugView
6. Grant analytics consent
7. Test page_view event:
   ✓ Click "Trigger Event"
   ✓ See success message
   ✓ Check console log
   ✓ Verify in GTM Preview
   ✓ Verify in GA4 DebugView
8. Test view_memorial event:
   ✓ Click "Trigger Event"
   ✓ Verify all parameters present
   ✓ Check in all tools
9. Continue for all 12 events...
10. Test consent blocking:
    ✓ Revoke consent
    ✓ Trigger event
    ✓ Verify blocked in console
    ✓ Grant consent
    ✓ Verify events fire again
11. Review Data Layer Viewer
12. Document any issues found
```

## Related Documentation

- [Event Tracking Reference](./event-tracking-reference.md) - Detailed event schemas
- [GTM Setup Guide](./gtm-setup-guide.md) - GTM configuration
- [GA4 Configuration Guide](./ga4-configuration-guide.md) - GA4 setup
- [Troubleshooting Guide](./troubleshooting-guide.md) - Common issues
- [Deployment Checklist](./deployment-checklist.md) - Pre-launch verification

## Support

If you encounter issues with the validation page:

1. Check browser console for errors
2. Verify environment configuration
3. Review troubleshooting guide
4. Check that all analytics JavaScript is loaded
5. Contact development team with:
   - Environment details
   - Browser and version
   - Console errors
   - Steps to reproduce
