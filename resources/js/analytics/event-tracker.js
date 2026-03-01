/**
 * EventTracker
 *
 * Tracks user interaction events and sends them to Google Analytics 4 via GTM.
 * Respects user consent choices and provides debug logging capabilities.
 *
 * Requirements: 8-19, 35.1, 35.2, 35.3
 */
export class EventTracker {
  constructor(consentManager, dataLayerManager) {
    this.consentManager = consentManager;
    this.dataLayer = dataLayerManager;
    this.debugMode = window.analyticsDebugMode || false;
  }

  /**
   * Check if tracking is allowed based on consent status
   *
   * @returns {boolean} True if analytics consent is granted
   * @private
   */
  canTrack() {
    const consent = this.consentManager.getConsent();

    // If no consent stored, default to denied
    if (!consent) {
      return false;
    }

    return consent.analytics === true;
  }

  /**
   * Sanitize event parameters to remove sensitive data
   *
   * @param {object} params - Event parameters to sanitize
   * @returns {object} Sanitized parameters
   * @private
   */
  sanitizeParams(params) {
    if (!params || typeof params !== 'object') {
      return {};
    }

    const sanitized = {};

    for (const [key, value] of Object.entries(params)) {
      // Skip null or undefined values
      if (value === null || value === undefined) {
        continue;
      }

      // Convert to string and limit length
      let sanitizedValue = String(value);

      // Limit string length to 100 characters
      if (sanitizedValue.length > 100) {
        sanitizedValue = sanitizedValue.substring(0, 100);
      }

      sanitized[key] = sanitizedValue;
    }

    return sanitized;
  }

  /**
   * Log event in debug mode
   *
   * @param {string} eventName - Name of the event
   * @param {object} params - Event parameters
   * @private
   */
  debugLog(eventName, params) {
    if (this.debugMode) {
      console.log(`[Analytics Debug] Event: ${eventName}`, params);
    }
  }

  /**
   * Track page view event
   *
   * @param {object} params - Page view parameters
   * @param {string} params.page_path - Current page path
   * @param {string} params.page_title - Current page title
   * @param {string} params.page_locale - Current locale
   * @param {string} params.page_type - Type of page
   *
   * Requirements: 8.1, 8.2, 8.3, 8.4, 8.5, 8.6
   */
  trackPageView(params) {
    if (!this.canTrack()) {
      this.debugLog('page_view', { ...params, blocked: 'consent_denied' });
      return;
    }

    const sanitized = this.sanitizeParams(params);

    this.dataLayer.push({
      event: 'page_view',
      ...sanitized
    });

    this.debugLog('page_view', sanitized);
  }

  /**
   * Track memorial profile view
   *
   * @param {object} params - Memorial view parameters
   * @param {string} params.memorial_id - Memorial ID
   * @param {string} params.memorial_slug - Memorial slug
   * @param {string} params.locale - Current locale
   * @param {boolean} params.is_public - Whether memorial is public
   *
   * Requirements: 9.1, 9.2, 9.3, 9.4, 9.5, 9.6
   */
  trackMemorialView(params) {
    if (!this.canTrack()) {
      this.debugLog('view_memorial', { ...params, blocked: 'consent_denied' });
      return;
    }

    const sanitized = this.sanitizeParams(params);

    this.dataLayer.push({
      event: 'view_memorial',
      ...sanitized
    });

    this.debugLog('view_memorial', sanitized);
  }

  /**
   * Track search query
   *
   * @param {object} params - Search parameters
   * @param {string} params.search_term - Search query
   * @param {number} params.results_count - Number of results
   * @param {string} params.locale - Current locale
   *
   * Requirements: 10.1, 10.2, 10.3, 10.4, 10.5
   */
  trackSearch(params) {
    if (!this.canTrack()) {
      this.debugLog('search', { ...params, blocked: 'consent_denied' });
      return;
    }

    const sanitized = this.sanitizeParams(params);

    this.dataLayer.push({
      event: 'search',
      ...sanitized
    });

    this.debugLog('search', sanitized);
  }

  /**
   * Track form submission
   *
   * @param {object} params - Form submission parameters
   * @param {string} params.form_type - Type of form
   * @param {string} params.locale - Current locale
   * @param {boolean} params.success - Whether submission succeeded
   * @param {string} [params.error_type] - Error type if failed
   *
   * Requirements: 11.1, 11.2, 11.3, 11.4, 11.5, 11.6, 11.7
   */
  trackFormSubmit(params) {
    if (!this.canTrack()) {
      this.debugLog('form_submit', { ...params, blocked: 'consent_denied' });
      return;
    }

    const sanitized = this.sanitizeParams(params);

    this.dataLayer.push({
      event: 'form_submit',
      ...sanitized
    });

    this.debugLog('form_submit', sanitized);
  }

  /**
   * Track user registration
   *
   * @param {object} params - Registration parameters
   * @param {string} params.locale - Current locale
   * @param {string} params.registration_method - Registration method
   *
   * Requirements: 12.1, 12.2, 12.3, 12.4
   */
  trackSignUp(params) {
    if (!this.canTrack()) {
      this.debugLog('sign_up', { ...params, blocked: 'consent_denied' });
      return;
    }

    const sanitized = this.sanitizeParams(params);

    this.dataLayer.push({
      event: 'sign_up',
      ...sanitized
    });

    this.debugLog('sign_up', sanitized);
  }

  /**
   * Track memorial creation
   *
   * @param {object} params - Memorial creation parameters
   * @param {string} params.locale - Current locale
   * @param {boolean} params.is_public - Whether memorial is public
   *
   * Requirements: 13.1, 13.2, 13.3, 13.4
   */
  trackMemorialCreation(params) {
    if (!this.canTrack()) {
      this.debugLog('create_memorial', { ...params, blocked: 'consent_denied' });
      return;
    }

    const sanitized = this.sanitizeParams(params);

    this.dataLayer.push({
      event: 'create_memorial',
      ...sanitized
    });

    this.debugLog('create_memorial', sanitized);
  }

  /**
   * Track media upload
   *
   * @param {object} params - Media upload parameters
   * @param {string} params.media_type - Type of media (image or video)
   * @param {string} params.memorial_id - Memorial ID
   * @param {number} params.file_size_kb - File size in KB
   *
   * Requirements: 14.1, 14.2, 14.3, 14.4, 14.5
   */
  trackMediaUpload(params) {
    if (!this.canTrack()) {
      this.debugLog('upload_media', { ...params, blocked: 'consent_denied' });
      return;
    }

    const sanitized = this.sanitizeParams(params);

    this.dataLayer.push({
      event: 'upload_media',
      ...sanitized
    });

    this.debugLog('upload_media', sanitized);
  }

  /**
   * Track tribute submission
   *
   * @param {object} params - Tribute submission parameters
   * @param {string} params.memorial_id - Memorial ID
   * @param {string} params.locale - Current locale
   * @param {string} params.tribute_type - Type of tribute (text, image, video)
   *
   * Requirements: 15.1, 15.2, 15.3, 15.4, 15.5
   */
  trackTributeSubmit(params) {
    if (!this.canTrack()) {
      this.debugLog('submit_tribute', { ...params, blocked: 'consent_denied' });
      return;
    }

    const sanitized = this.sanitizeParams(params);

    this.dataLayer.push({
      event: 'submit_tribute',
      ...sanitized
    });

    this.debugLog('submit_tribute', sanitized);
  }

  /**
   * Track navigation click
   *
   * @param {object} params - Navigation click parameters
   * @param {string} params.menu_item - Menu item text
   * @param {string} params.destination_url - Destination URL
   * @param {string} params.locale - Current locale
   *
   * Requirements: 16.1, 16.2, 16.3, 16.4, 16.5
   */
  trackNavigationClick(params) {
    if (!this.canTrack()) {
      this.debugLog('navigation_click', { ...params, blocked: 'consent_denied' });
      return;
    }

    const sanitized = this.sanitizeParams(params);

    this.dataLayer.push({
      event: 'navigation_click',
      ...sanitized
    });

    this.debugLog('navigation_click', sanitized);
  }

  /**
   * Track external link click
   *
   * @param {object} params - Outbound click parameters
   * @param {string} params.link_url - External link URL
   * @param {string} params.link_text - Link text
   * @param {string} params.page_location - Current page location
   *
   * Requirements: 17.1, 17.2, 17.3, 17.4, 17.5
   */
  trackOutboundClick(params) {
    if (!this.canTrack()) {
      this.debugLog('outbound_click', { ...params, blocked: 'consent_denied' });
      return;
    }

    const sanitized = this.sanitizeParams(params);

    this.dataLayer.push({
      event: 'outbound_click',
      ...sanitized
    });

    this.debugLog('outbound_click', sanitized);
  }

  /**
   * Track file download
   *
   * @param {object} params - File download parameters
   * @param {string} params.file_type - Type of file
   * @param {string} params.file_name - File name
   * @param {string} params.file_extension - File extension
   *
   * Requirements: 18.1, 18.2, 18.3, 18.4, 18.5
   */
  trackFileDownload(params) {
    if (!this.canTrack()) {
      this.debugLog('file_download', { ...params, blocked: 'consent_denied' });
      return;
    }

    const sanitized = this.sanitizeParams(params);

    this.dataLayer.push({
      event: 'file_download',
      ...sanitized
    });

    this.debugLog('file_download', sanitized);
  }

  /**
   * Track JavaScript error
   *
   * @param {object} params - Error parameters
   * @param {string} params.error_type - Type of error
   * @param {string} params.error_message - Error message
   * @param {string} params.page_url - Page URL where error occurred
   * @param {string} params.user_agent - User agent string
   *
   * Requirements: 19.1, 19.2, 19.3, 19.4, 19.5, 19.6
   */
  trackError(params) {
    if (!this.canTrack()) {
      this.debugLog('error_event', { ...params, blocked: 'consent_denied' });
      return;
    }

    const sanitized = this.sanitizeParams(params);

    this.dataLayer.push({
      event: 'error_event',
      ...sanitized
    });

    this.debugLog('error_event', sanitized);
  }
}
