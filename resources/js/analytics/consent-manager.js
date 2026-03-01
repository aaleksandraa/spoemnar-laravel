/**
 * Cookie Consent Manager
 *
 * Manages user cookie consent preferences with GDPR compliance.
 * Handles consent storage, expiration, versioning, and GTM consent mode integration.
 */
export class ConsentManager {
    constructor(options = {}) {
        this.storageKey = options.storageKey || 'cookie_consent';
        this.version = options.version || 1;
        this.expirationMonths = options.expirationMonths || 12;
    }

    /**
     * Check if consent banner should be shown
     * Returns true if no consent is stored, consent is expired, or version has changed
     *
     * @returns {boolean}
     */
    shouldShowBanner() {
        const consent = this.getConsent();

        // No consent stored
        if (!consent) {
            return true;
        }

        // Consent version has changed
        if (consent.version !== this.version) {
            return true;
        }

        // Consent has expired
        if (this.isConsentExpired(consent)) {
            return true;
        }

        return false;
    }

    /**
     * Get current consent state from localStorage
     *
     * @returns {Object|null} Consent state or null if not found
     */
    getConsent() {
        try {
            const stored = localStorage.getItem(this.storageKey);
            if (!stored) {
                return null;
            }

            const consent = JSON.parse(stored);
            return consent;
        } catch (error) {
            console.error('Error reading consent from localStorage:', error);
            return null;
        }
    }

    /**
     * Save consent preferences to localStorage
     *
     * @param {Object} preferences - User consent preferences
     * @param {boolean} preferences.necessary - Always true (required cookies)
     * @param {boolean} preferences.analytics - User choice for analytics cookies
     * @param {boolean} preferences.marketing - User choice for marketing cookies
     */
    saveConsent(preferences) {
        const now = Date.now();
        const expiresAt = now + (this.expirationMonths * 30 * 24 * 60 * 60 * 1000);

        const consent = {
            version: this.version,
            timestamp: now,
            expiresAt: expiresAt,
            necessary: true, // Always true
            analytics: preferences.analytics || false,
            marketing: preferences.marketing || false
        };

        try {
            localStorage.setItem(this.storageKey, JSON.stringify(consent));

            // Update GTM consent mode
            this.updateGTMConsent(consent);

            return true;
        } catch (error) {
            console.error('Error saving consent to localStorage:', error);
            return false;
        }
    }

    /**
     * Check if consent has expired (older than 12 months)
     *
     * @param {Object} consent - Consent state object
     * @returns {boolean}
     */
    isConsentExpired(consent) {
        if (!consent || !consent.expiresAt) {
            return true;
        }

        return Date.now() > consent.expiresAt;
    }

    /**
     * Delete stored consent preferences
     */
    deleteConsent() {
        try {
            localStorage.removeItem(this.storageKey);
            return true;
        } catch (error) {
            console.error('Error deleting consent from localStorage:', error);
            return false;
        }
    }

    /**
     * Update GTM consent mode based on user preferences
     * Pushes consent updates to the dataLayer for GTM
     *
     * @param {Object} preferences - User consent preferences
     */
    updateGTMConsent(preferences) {
        // Ensure dataLayer exists
        window.dataLayer = window.dataLayer || [];

        // Push consent update to dataLayer
        window.dataLayer.push({
            event: 'consent_update',
            consent: {
                analytics_storage: preferences.analytics ? 'granted' : 'denied',
                ad_storage: preferences.marketing ? 'granted' : 'denied',
                functionality_storage: 'granted', // Always granted for necessary cookies
                personalization_storage: preferences.marketing ? 'granted' : 'denied',
                security_storage: 'granted' // Always granted for security
            }
        });

        // Also use gtag consent API if available
        if (typeof gtag === 'function') {
            gtag('consent', 'update', {
                'analytics_storage': preferences.analytics ? 'granted' : 'denied',
                'ad_storage': preferences.marketing ? 'granted' : 'denied',
                'functionality_storage': 'granted',
                'personalization_storage': preferences.marketing ? 'granted' : 'denied',
                'security_storage': 'granted'
            });
        }
    }

    /**
     * Initialize default consent mode (before user choice)
     * Should be called before GTM loads
     */
    initializeDefaultConsent() {
        window.dataLayer = window.dataLayer || [];

        // Check if user has existing consent
        const consent = this.getConsent();

        if (consent && !this.isConsentExpired(consent) && consent.version === this.version) {
            // User has valid consent, apply it
            this.updateGTMConsent(consent);
        } else {
            // No valid consent, set default to denied
            window.dataLayer.push({
                event: 'consent_default',
                consent: {
                    analytics_storage: 'denied',
                    ad_storage: 'denied',
                    functionality_storage: 'granted',
                    personalization_storage: 'denied',
                    security_storage: 'granted'
                }
            });

            if (typeof gtag === 'function') {
                gtag('consent', 'default', {
                    'analytics_storage': 'denied',
                    'ad_storage': 'denied',
                    'functionality_storage': 'granted',
                    'personalization_storage': 'denied',
                    'security_storage': 'granted'
                });
            }
        }
    }
}
