/**
 * Cookie Banner Interactions
 *
 * Handles user interactions with the cookie consent banner.
 * Manages banner visibility, button clicks, and detailed settings toggle.
 */
export class CookieBannerUI {
    constructor(consentManager) {
        this.consentManager = consentManager;
        this.banner = null;
        this.detailsSection = null;
        this.mainContent = null;

        this.init();
    }

    /**
     * Initialize the cookie banner UI
     */
    init() {
        // Wait for DOM to be ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.setup());
        } else {
            this.setup();
        }
    }

    /**
     * Set up the banner elements and event listeners
     */
    setup() {
        // Get banner elements
        this.banner = document.getElementById('cookie-consent-banner');
        this.detailsSection = document.getElementById('cookie-details');
        this.mainContent = this.banner?.querySelector('.cookie-banner__content');

        if (!this.banner) {
            console.warn('Cookie consent banner element not found');
            return;
        }

        // Show banner if needed
        if (this.consentManager.shouldShowBanner()) {
            this.showBanner();
        }

        // Attach event listeners
        this.attachEventListeners();

        // Load existing preferences into detailed settings
        this.loadPreferences();
    }

    /**
     * Attach event listeners to banner buttons
     */
    attachEventListeners() {
        // Accept All button
        const acceptAllBtn = document.getElementById('cookie-accept-all');
        if (acceptAllBtn) {
            acceptAllBtn.addEventListener('click', () => this.handleAcceptAll());
        }

        // Reject All button
        const rejectAllBtn = document.getElementById('cookie-reject-all');
        if (rejectAllBtn) {
            rejectAllBtn.addEventListener('click', () => this.handleRejectAll());
        }

        // Customize button
        const customizeBtn = document.getElementById('cookie-customize');
        if (customizeBtn) {
            customizeBtn.addEventListener('click', () => this.showDetails());
        }

        // Save Preferences button (in detailed settings)
        const saveBtn = document.getElementById('cookie-save-preferences');
        if (saveBtn) {
            saveBtn.addEventListener('click', () => this.handleSavePreferences());
        }

        // Back button (in detailed settings)
        const backBtn = document.getElementById('cookie-back');
        if (backBtn) {
            backBtn.addEventListener('click', () => this.hideDetails());
        }
    }

    /**
     * Handle Accept All button click
     */
    handleAcceptAll() {
        const preferences = {
            necessary: true,
            analytics: true,
            marketing: true
        };

        this.consentManager.saveConsent(preferences);
        this.hideBanner();

        // Reload page to apply consent
        window.location.reload();
    }

    /**
     * Handle Reject All button click
     */
    handleRejectAll() {
        const preferences = {
            necessary: true,
            analytics: false,
            marketing: false
        };

        this.consentManager.saveConsent(preferences);
        this.hideBanner();

        // Reload page to apply consent
        window.location.reload();
    }

    /**
     * Handle Save Preferences button click (from detailed settings)
     */
    handleSavePreferences() {
        const analyticsCheckbox = document.getElementById('cookie-analytics');
        const marketingCheckbox = document.getElementById('cookie-marketing');

        const preferences = {
            necessary: true,
            analytics: analyticsCheckbox?.checked || false,
            marketing: marketingCheckbox?.checked || false
        };

        this.consentManager.saveConsent(preferences);
        this.hideBanner();

        // Reload page to apply consent
        window.location.reload();
    }

    /**
     * Show the cookie banner
     */
    showBanner() {
        if (this.banner) {
            this.banner.style.display = 'block';

            // Set focus to the banner for accessibility
            this.banner.setAttribute('tabindex', '-1');
            this.banner.focus();
        }
    }

    /**
     * Hide the cookie banner
     */
    hideBanner() {
        if (this.banner) {
            this.banner.style.display = 'none';
        }
    }

    /**
     * Show detailed cookie settings
     */
    showDetails() {
        if (this.mainContent && this.detailsSection) {
            this.mainContent.style.display = 'none';
            this.detailsSection.style.display = 'block';

            // Set focus to details section for accessibility
            this.detailsSection.setAttribute('tabindex', '-1');
            this.detailsSection.focus();
        }
    }

    /**
     * Hide detailed cookie settings and show main content
     */
    hideDetails() {
        if (this.mainContent && this.detailsSection) {
            this.detailsSection.style.display = 'none';
            this.mainContent.style.display = 'block';

            // Return focus to main content
            this.mainContent.setAttribute('tabindex', '-1');
            this.mainContent.focus();
        }
    }

    /**
     * Load existing preferences into the detailed settings checkboxes
     */
    loadPreferences() {
        const consent = this.consentManager.getConsent();

        if (consent) {
            const analyticsCheckbox = document.getElementById('cookie-analytics');
            const marketingCheckbox = document.getElementById('cookie-marketing');

            if (analyticsCheckbox) {
                analyticsCheckbox.checked = consent.analytics || false;
            }

            if (marketingCheckbox) {
                marketingCheckbox.checked = consent.marketing || false;
            }
        }
    }

    /**
     * Programmatically show the banner (for cookie settings page)
     */
    show() {
        this.showBanner();
    }

    /**
     * Programmatically hide the banner
     */
    hide() {
        this.hideBanner();
    }
}
