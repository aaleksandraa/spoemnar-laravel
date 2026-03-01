# Event Tracking Reference

Complete reference for all 12 event types tracked in the memorial application, including parameter schemas, trigger conditions, consent requirements, and code examples.

## Table of Contents

1. [Event Overview](#event-overview)
2. [Event Type Details](#event-type-details)
   - [1. page_view](#1-page_view)
   - [2. view_memorial](#2-view_memorial)
   - [3. search](#3-search)
   - [4. form_submit](#4-form_submit)
   - [5. sign_up](#5-sign_up)
   - [6. create_memorial](#6-create_memorial)
   - [7. upload_media](#7-upload_media)
   - [8. submit_tribute](#8-submit_tribute)
   - [9. navigation_click](#9-navigation_click)
   - [10. outbound_click](#10-outbound_click)
   - [11. file_download](#11-file_download)
   - [12. error_event](#12-error_event)
3. [Implementation Guidelines](#implementation-guidelines)

---

## Event Overview

### Event Categories

| Category | Events | Purpose |
|----------|--------|---------|
| **Page Tracking** | page_view, view_memorial | Track page navigation and content views |
| **User Actions** | search, navigation_click, outbound_click, file_download | Track user interactions |
| **Conversions** | form_submit, sign_up, create_memorial | Track conversion events |
| **Content Creation** | upload_media, submit_tribute | Track user-generated content |
| **Error Monitoring** | error_event | Track JavaScript errors |

### Consent Requirements

All events require **analytics consent** to be granted. Events are automatically blocked when consent is denied.

```javascript
// Consent check is built into EventTracker
if (!this.canTrack()) {
  // Event is not sent
  return;
}
```

---

## Event Type Details

### 1. page_view

Tracks when a user views a page.

#### Schema

```typescript
{
  event: 'page_view',
  page_path: string,      // Current page path (e.g., '/memorials/john-doe')
  page_title: string,     // Page title from document.title
  page_locale: string,    // Current locale (bs, sr, hr, de, en, it)
  page_type: string       // Page type (home, memorial, search, contact, etc.)
}
```

#### Trigger Conditions

- Automatically triggered on every page load
- Triggered after consent is granted (if initially denied)

#### Consent Requirement

✅ Requires analytics consent

#### Code Example

```javascript
// Automatically called in app.js on page load
eventTracker.trackPageView({
  page_path: window.location.pathname,
  page_title: document.title,
  page_locale: document.documentElement.lang,
  page_type: window.dataLayer?.[0]?.page_type || 'unknown'
});
```

#### GTM Configuration

- **Trigger**: Custom Event `page_view`
- **Tag Type**: GA4 Event
- **Event Name**: `page_view`
- **Parameters**: page_path, page_title, page_locale, page_type

---

### 2. view_memorial

Tracks when a user views a memorial profile page.

#### Schema

```typescript
{
  event: 'view_memorial',
  memorial_id: string,    // Memorial database ID
  memorial_slug: string,  // Memorial URL slug
  locale: string,         // Current locale
  is_public: boolean      // Whether memorial is publicly visible
}
```

#### Trigger Conditions

- Triggered when memorial profile page loads
- Called from memorial show view

#### Consent Requirement

✅ Requires analytics consent

#### Code Example

```javascript
// In memorial profile view
eventTracker.trackMemorialView({
  memorial_id: '123',
  memorial_slug: 'john-doe',
  locale: 'en',
  is_public: true
});
```

#### Blade Template Example

```blade
@section('scripts')
<script>
  if (window.eventTracker) {
    window.eventTracker.trackMemorialView({
      memorial_id: '{{ $memorial->id }}',
      memorial_slug: '{{ $memorial->slug }}',
      locale: '{{ app()->getLocale() }}',
      is_public: {{ $memorial->is_public ? 'true' : 'false' }}
    });
  }
</script>
@endsection
```

#### GTM Configuration

- **Trigger**: Custom Event `view_memorial`
- **Tag Type**: GA4 Event
- **Event Name**: `view_memorial`
- **Parameters**: memorial_id, memorial_slug, locale, is_public

---

### 3. search

Tracks when a user performs a search query.

#### Schema

```typescript
{
  event: 'search',
  search_term: string,    // User's search query
  results_count: number,  // Number of results returned
  locale: string          // Current locale
}
```

#### Trigger Conditions

- Triggered when search is performed
- Called from search results page or search component

#### Consent Requirement

✅ Requires analytics consent

#### Code Example

```javascript
// After search is performed
eventTracker.trackSearch({
  search_term: 'John Doe',
  results_count: 5,
  locale: 'en'
});
```

#### Laravel Controller Example

```php
public function search(Request $request)
{
    $query = $request->input('q');
    $results = Memorial::search($query)->get();
    
    return view('search.results', [
        'query' => $query,
        'results' => $results,
        'resultsCount' => $results->count()
    ]);
}
```

#### Blade Template Example

```blade
@section('scripts')
<script>
  if (window.eventTracker) {
    window.eventTracker.trackSearch({
      search_term: '{{ $query }}',
      results_count: {{ $resultsCount }},
      locale: '{{ app()->getLocale() }}'
    });
  }
</script>
@endsection
```

#### GTM Configuration

- **Trigger**: Custom Event `search`
- **Tag Type**: GA4 Event
- **Event Name**: `search`
- **Parameters**: search_term, results_count, locale

---

### 4. form_submit

Tracks when a user submits a form (contact, memorial creation, etc.).

#### Schema

```typescript
{
  event: 'form_submit',
  form_type: string,      // Type of form (contact, memorial_create, etc.)
  locale: string,         // Current locale
  success: boolean,       // Whether submission succeeded
  error_type?: string     // Error type if submission failed (optional)
}
```

#### Trigger Conditions

- Triggered on form submission (both success and failure)
- Called from form submission handlers

#### Consent Requirement

✅ Requires analytics consent

#### Code Example - Success

```javascript
// After successful form submission
eventTracker.trackFormSubmit({
  form_type: 'contact',
  locale: 'en',
  success: true
});
```

#### Code Example - Failure

```javascript
// After failed form submission
eventTracker.trackFormSubmit({
  form_type: 'contact',
  locale: 'en',
  success: false,
  error_type: 'validation_error'
});
```

#### Alpine.js Form Example

```html
<form x-data="contactForm()" @submit.prevent="submitForm">
  <!-- form fields -->
</form>

<script>
function contactForm() {
  return {
    async submitForm() {
      try {
        const response = await axios.post('/contact', this.formData);
        
        // Track success
        window.eventTracker.trackFormSubmit({
          form_type: 'contact',
          locale: document.documentElement.lang,
          success: true
        });
        
        // Show success message
      } catch (error) {
        // Track failure
        window.eventTracker.trackFormSubmit({
          form_type: 'contact',
          locale: document.documentElement.lang,
          success: false,
          error_type: error.response?.data?.error || 'unknown_error'
        });
        
        // Show error message
      }
    }
  }
}
</script>
```

#### GTM Configuration

- **Trigger**: Custom Event `form_submit`
- **Tag Type**: GA4 Event
- **Event Name**: `form_submit`
- **Parameters**: form_type, locale, success, error_type

---

### 5. sign_up

Tracks when a user completes registration.

#### Schema

```typescript
{
  event: 'sign_up',
  locale: string,              // Current locale
  registration_method: string  // Registration method (email, social, etc.)
}
```

#### Trigger Conditions

- Triggered after successful user registration
- Called from registration success handler

#### Consent Requirement

✅ Requires analytics consent

#### Code Example

```javascript
// After successful registration
eventTracker.trackSignUp({
  locale: 'en',
  registration_method: 'email'
});
```

#### Laravel Controller Example

```php
public function register(Request $request)
{
    // Validate and create user
    $user = User::create([...]);
    
    // Log in user
    Auth::login($user);
    
    return redirect()->route('dashboard')
        ->with('track_signup', [
            'locale' => app()->getLocale(),
            'registration_method' => 'email'
        ]);
}
```

#### Blade Template Example

```blade
@if(session('track_signup'))
<script>
  if (window.eventTracker) {
    window.eventTracker.trackSignUp({
      locale: '{{ session('track_signup.locale') }}',
      registration_method: '{{ session('track_signup.registration_method') }}'
    });
  }
</script>
@endif
```

#### GTM Configuration

- **Trigger**: Custom Event `sign_up`
- **Tag Type**: GA4 Event
- **Event Name**: `sign_up`
- **Parameters**: locale, registration_method

---

### 6. create_memorial

Tracks when a user creates a new memorial.

#### Schema

```typescript
{
  event: 'create_memorial',
  locale: string,     // Current locale
  is_public: boolean  // Whether memorial is set to public
}
```

#### Trigger Conditions

- Triggered after successful memorial creation
- Called from memorial creation success handler

#### Consent Requirement

✅ Requires analytics consent

#### Code Example

```javascript
// After memorial is created
eventTracker.trackMemorialCreation({
  locale: 'en',
  is_public: true
});
```

#### Implementation Pattern

Similar to sign_up event - track after successful creation in controller, pass data via session, trigger in view.

#### GTM Configuration

- **Trigger**: Custom Event `create_memorial`
- **Tag Type**: GA4 Event
- **Event Name**: `create_memorial`
- **Parameters**: locale, is_public

---

### 7. upload_media

Tracks when a user uploads an image or video to a memorial.

#### Schema

```typescript
{
  event: 'upload_media',
  media_type: string,     // Type of media (image or video)
  memorial_id: string,    // Memorial ID
  file_size_kb: number    // File size in kilobytes
}
```

#### Trigger Conditions

- Triggered after successful media upload
- Called from upload success handler

#### Consent Requirement

✅ Requires analytics consent

#### Code Example

```javascript
// After successful upload
eventTracker.trackMediaUpload({
  media_type: 'image',
  memorial_id: '123',
  file_size_kb: 1024
});
```

#### Upload Handler Example

```javascript
async function uploadMedia(file, memorialId) {
  const formData = new FormData();
  formData.append('file', file);
  formData.append('memorial_id', memorialId);
  
  try {
    const response = await axios.post('/api/media/upload', formData);
    
    // Track upload
    window.eventTracker.trackMediaUpload({
      media_type: file.type.startsWith('image/') ? 'image' : 'video',
      memorial_id: memorialId,
      file_size_kb: Math.round(file.size / 1024)
    });
    
    return response.data;
  } catch (error) {
    console.error('Upload failed:', error);
    throw error;
  }
}
```

#### GTM Configuration

- **Trigger**: Custom Event `upload_media`
- **Tag Type**: GA4 Event
- **Event Name**: `upload_media`
- **Parameters**: media_type, memorial_id, file_size_kb

---

### 8. submit_tribute

Tracks when a user submits a tribute to a memorial.

#### Schema

```typescript
{
  event: 'submit_tribute',
  memorial_id: string,    // Memorial ID
  locale: string,         // Current locale
  tribute_type: string    // Type of tribute (text, image, video)
}
```

#### Trigger Conditions

- Triggered after successful tribute submission
- Called from tribute submission handler

#### Consent Requirement

✅ Requires analytics consent

#### Code Example

```javascript
// After tribute is submitted
eventTracker.trackTributeSubmit({
  memorial_id: '123',
  locale: 'en',
  tribute_type: 'text'
});
```

#### GTM Configuration

- **Trigger**: Custom Event `submit_tribute`
- **Tag Type**: GA4 Event
- **Event Name**: `submit_tribute`
- **Parameters**: memorial_id, locale, tribute_type

---

### 9. navigation_click

Tracks when a user clicks a main navigation link.

#### Schema

```typescript
{
  event: 'navigation_click',
  menu_item: string,        // Menu item text
  destination_url: string,  // Destination URL
  locale: string            // Current locale
}
```

#### Trigger Conditions

- Triggered when user clicks navigation links
- Automatically attached to nav links in app.js

#### Consent Requirement

✅ Requires analytics consent

#### Code Example

```javascript
// Automatically attached in app.js
document.querySelectorAll('nav a').forEach(link => {
  link.addEventListener('click', (e) => {
    eventTracker.trackNavigationClick({
      menu_item: e.target.textContent.trim(),
      destination_url: e.target.href,
      locale: document.documentElement.lang
    });
  });
});
```

#### GTM Configuration

- **Trigger**: Custom Event `navigation_click`
- **Tag Type**: GA4 Event
- **Event Name**: `navigation_click`
- **Parameters**: menu_item, destination_url, locale

---

### 10. outbound_click

Tracks when a user clicks an external link.

#### Schema

```typescript
{
  event: 'outbound_click',
  link_url: string,         // External link URL
  link_text: string,        // Link text
  page_location: string     // Current page URL
}
```

#### Trigger Conditions

- Triggered when user clicks external links
- Automatically attached to external links in app.js

#### Consent Requirement

✅ Requires analytics consent

#### Code Example

```javascript
// Automatically attached in app.js
document.querySelectorAll('a[href^="http"]').forEach(link => {
  if (!link.href.includes(window.location.hostname)) {
    link.addEventListener('click', (e) => {
      eventTracker.trackOutboundClick({
        link_url: e.target.href,
        link_text: e.target.textContent.trim(),
        page_location: window.location.href
      });
    });
  }
});
```

#### GTM Configuration

- **Trigger**: Custom Event `outbound_click`
- **Tag Type**: GA4 Event
- **Event Name**: `outbound_click`
- **Parameters**: link_url, link_text, page_location

---

### 11. file_download

Tracks when a user downloads a file.

#### Schema

```typescript
{
  event: 'file_download',
  file_type: string,        // Type of file (document, image, etc.)
  file_name: string,        // File name
  file_extension: string    // File extension (pdf, jpg, etc.)
}
```

#### Trigger Conditions

- Triggered when user clicks download links
- Automatically attached to download links in app.js

#### Consent Requirement

✅ Requires analytics consent

#### Code Example

```javascript
// Automatically attached in app.js
document.querySelectorAll('a[download], a[href$=".pdf"], a[href$=".doc"]').forEach(link => {
  link.addEventListener('click', (e) => {
    const url = new URL(e.target.href);
    const fileName = url.pathname.split('/').pop();
    const extension = fileName.split('.').pop();
    
    eventTracker.trackFileDownload({
      file_type: getFileType(extension),
      file_name: fileName,
      file_extension: extension
    });
  });
});

function getFileType(extension) {
  const types = {
    'pdf': 'document',
    'doc': 'document',
    'docx': 'document',
    'jpg': 'image',
    'png': 'image',
    'mp4': 'video'
  };
  return types[extension] || 'other';
}
```

#### GTM Configuration

- **Trigger**: Custom Event `file_download`
- **Tag Type**: GA4 Event
- **Event Name**: `file_download`
- **Parameters**: file_type, file_name, file_extension

---

### 12. error_event

Tracks JavaScript errors for monitoring and debugging.

#### Schema

```typescript
{
  event: 'error_event',
  error_type: string,       // Type of error (TypeError, ReferenceError, etc.)
  error_message: string,    // Error message
  page_url: string,         // Page where error occurred
  user_agent: string        // User's browser user agent
}
```

#### Trigger Conditions

- Triggered when JavaScript error occurs
- Automatically captured by global error handler

#### Consent Requirement

✅ Requires analytics consent

#### Code Example

```javascript
// Global error handler in app.js
window.addEventListener('error', (event) => {
  eventTracker.trackError({
    error_type: event.error?.name || 'Error',
    error_message: event.message,
    page_url: window.location.href,
    user_agent: navigator.userAgent
  });
});

// Promise rejection handler
window.addEventListener('unhandledrejection', (event) => {
  eventTracker.trackError({
    error_type: 'UnhandledPromiseRejection',
    error_message: event.reason?.message || String(event.reason),
    page_url: window.location.href,
    user_agent: navigator.userAgent
  });
});
```

#### GTM Configuration

- **Trigger**: Custom Event `error_event`
- **Tag Type**: GA4 Event
- **Event Name**: `error_event`
- **Parameters**: error_type, error_message, page_url, user_agent

---

## Implementation Guidelines

### General Principles

1. **Consent First**: Always check consent before tracking
2. **Sanitize Data**: Remove sensitive information from parameters
3. **Limit Length**: Keep parameter values under 100 characters
4. **Error Handling**: Gracefully handle tracking failures
5. **Debug Mode**: Use debug logging during development

### Parameter Sanitization

The EventTracker automatically sanitizes parameters:

```javascript
sanitizeParams(params) {
  const sanitized = {};
  
  for (const [key, value] of Object.entries(params)) {
    if (value === null || value === undefined) {
      continue;
    }
    
    let sanitizedValue = String(value);
    
    // Limit length to 100 characters
    if (sanitizedValue.length > 100) {
      sanitizedValue = sanitizedValue.substring(0, 100);
    }
    
    sanitized[key] = sanitizedValue;
  }
  
  return sanitized;
}
```

### Debug Mode

Enable debug mode to see tracking in console:

```env
ANALYTICS_DEBUG_MODE=true
```

Debug output example:

```
[Analytics Debug] Event: page_view {page_path: "/memorials/john-doe", page_title: "John Doe Memorial", ...}
[Analytics Debug] Event: view_memorial {memorial_id: "123", memorial_slug: "john-doe", ...}
```

### Testing Checklist

For each event type, verify:

- ✅ Event fires when expected
- ✅ All required parameters are present
- ✅ Parameter values are correct
- ✅ Event respects consent (blocked when denied)
- ✅ Event appears in GA4 DebugView
- ✅ Event appears in GTM Preview mode

### Common Patterns

#### Pattern 1: Track After Server Action

```javascript
// After successful server action
axios.post('/api/action', data)
  .then(response => {
    // Track success
    eventTracker.trackEvent({...});
  })
  .catch(error => {
    // Track failure if applicable
  });
```

#### Pattern 2: Track From Blade Template

```blade
@section('scripts')
<script>
  if (window.eventTracker) {
    window.eventTracker.trackEvent({
      param: '{{ $value }}'
    });
  }
</script>
@endsection
```

#### Pattern 3: Track User Interaction

```javascript
element.addEventListener('click', (e) => {
  eventTracker.trackEvent({
    param: e.target.value
  });
});
```

---

## Next Steps

- [GTM Setup Guide](./gtm-setup-guide.md) - Configure GTM tags for these events
- [GA4 Configuration Guide](./ga4-configuration-guide.md) - Set up GA4 property
- [Troubleshooting Guide](./troubleshooting-guide.md) - Debug tracking issues
