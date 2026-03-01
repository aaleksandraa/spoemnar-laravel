/**
 * DataLayerManager
 *
 * Manages the global dataLayer array for Google Tag Manager integration.
 * Provides methods to push events, retrieve state, and update page context.
 *
 * Requirements: 2.6, 2.7
 */
export class DataLayerManager {
  constructor() {
    // Initialize or reference existing dataLayer
    this.dataLayer = window.dataLayer || [];
    window.dataLayer = this.dataLayer;
  }

  /**
   * Push event to data layer
   *
   * @param {object} event - Event object to push to dataLayer
   */
  push(event) {
    if (!event || typeof event !== 'object') {
      console.warn('DataLayerManager: Invalid event object', event);
      return;
    }

    this.dataLayer.push(event);
  }

  /**
   * Get current data layer state
   *
   * @returns {array} Current dataLayer array
   */
  getState() {
    return this.dataLayer;
  }

  /**
   * Update page context in data layer
   *
   * @param {object} context - Page context object with properties to update
   */
  updatePageContext(context) {
    if (!context || typeof context !== 'object') {
      console.warn('DataLayerManager: Invalid context object', context);
      return;
    }

    this.push({
      event: 'page_context_update',
      ...context
    });
  }
}
