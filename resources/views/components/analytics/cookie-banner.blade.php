{{-- Cookie Consent Banner Component --}}
<div id="cookie-consent-banner"
     class="cookie-banner"
     role="dialog"
     aria-labelledby="cookie-banner-title"
     aria-describedby="cookie-banner-description"
     style="display: none;">

    <div class="cookie-banner__content">
        <h2 id="cookie-banner-title" class="cookie-banner__title">
            {{ __('cookies.banner.title') }}
        </h2>

        <p id="cookie-banner-description" class="cookie-banner__description">
            {{ __('cookies.banner.description') }}
        </p>

        <div class="cookie-banner__actions">
            <button type="button"
                    id="cookie-accept-all"
                    class="btn btn-primary cookie-banner__btn">
                {{ __('cookies.banner.accept_all') }}
            </button>

            <button type="button"
                    id="cookie-reject-all"
                    class="btn btn-secondary cookie-banner__btn">
                {{ __('cookies.banner.reject_all') }}
            </button>

            <button type="button"
                    id="cookie-customize"
                    class="btn btn-link cookie-banner__btn">
                {{ __('cookies.banner.customize') }}
            </button>
        </div>

        <a href="{{ route('privacy', ['locale' => app()->getLocale()]) }}"
           class="cookie-banner__privacy-link"
           target="_blank"
           rel="noopener noreferrer">
            {{ __('cookies.banner.privacy_policy') }}
        </a>
    </div>

    {{-- Detailed Cookie Settings (shown when Customize is clicked) --}}
    <div id="cookie-details" class="cookie-details" style="display: none;">
        <div class="cookie-details__content">
            <h3 class="cookie-details__title">
                {{ __('cookies.details.title') }}
            </h3>

            {{-- Necessary Cookies (always enabled) --}}
            <div class="cookie-category">
                <div class="cookie-category__header">
                    <div class="cookie-category__info">
                        <h4 class="cookie-category__name">
                            {{ __('cookies.categories.necessary.title') }}
                        </h4>
                        <p class="cookie-category__description">
                            {{ __('cookies.categories.necessary.description') }}
                        </p>
                    </div>
                    <div class="cookie-category__toggle">
                        <input type="checkbox"
                               id="cookie-necessary"
                               checked
                               disabled
                               aria-label="{{ __('cookies.categories.necessary.title') }}">
                        <label for="cookie-necessary" class="sr-only">
                            {{ __('cookies.categories.necessary.title') }}
                        </label>
                        <span class="cookie-category__status">
                            {{ __('cookies.always_active') }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Analytics Cookies --}}
            <div class="cookie-category">
                <div class="cookie-category__header">
                    <div class="cookie-category__info">
                        <h4 class="cookie-category__name">
                            {{ __('cookies.categories.analytics.title') }}
                        </h4>
                        <p class="cookie-category__description">
                            {{ __('cookies.categories.analytics.description') }}
                        </p>
                    </div>
                    <div class="cookie-category__toggle">
                        <label class="toggle-switch">
                            <input type="checkbox"
                                   id="cookie-analytics"
                                   aria-label="{{ __('cookies.categories.analytics.title') }}">
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>
            </div>

            {{-- Marketing Cookies --}}
            <div class="cookie-category">
                <div class="cookie-category__header">
                    <div class="cookie-category__info">
                        <h4 class="cookie-category__name">
                            {{ __('cookies.categories.marketing.title') }}
                        </h4>
                        <p class="cookie-category__description">
                            {{ __('cookies.categories.marketing.description') }}
                        </p>
                    </div>
                    <div class="cookie-category__toggle">
                        <label class="toggle-switch">
                            <input type="checkbox"
                                   id="cookie-marketing"
                                   aria-label="{{ __('cookies.categories.marketing.title') }}">
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="cookie-details__actions">
                <button type="button"
                        id="cookie-save-preferences"
                        class="btn btn-primary">
                    {{ __('cookies.details.save_preferences') }}
                </button>

                <button type="button"
                        id="cookie-back"
                        class="btn btn-secondary">
                    {{ __('cookies.details.back') }}
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Cookie Banner Styles */
.cookie-banner {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: #ffffff;
    border-top: 2px solid #e5e7eb;
    box-shadow: 0 -4px 6px -1px rgba(0, 0, 0, 0.1);
    z-index: 9999;
    padding: 1.5rem;
    max-height: 90vh;
    overflow-y: auto;
}

.cookie-banner__content {
    max-width: 1200px;
    margin: 0 auto;
}

.cookie-banner__title {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 0.75rem;
    color: #111827;
}

.cookie-banner__description {
    font-size: 0.875rem;
    color: #4b5563;
    margin-bottom: 1rem;
    line-height: 1.5;
}

.cookie-banner__actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.cookie-banner__btn {
    padding: 0.625rem 1.25rem;
    font-size: 0.875rem;
    border-radius: 0.375rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
}

.cookie-banner__privacy-link {
    display: inline-block;
    font-size: 0.875rem;
    color: #2563eb;
    text-decoration: underline;
}

.cookie-banner__privacy-link:hover {
    color: #1d4ed8;
}

/* Cookie Details Styles */
.cookie-details {
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid #e5e7eb;
}

.cookie-details__title {
    font-size: 1.125rem;
    font-weight: 600;
    margin-bottom: 1rem;
    color: #111827;
}

.cookie-category {
    margin-bottom: 1.5rem;
    padding: 1rem;
    background: #f9fafb;
    border-radius: 0.5rem;
}

.cookie-category__header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1rem;
}

.cookie-category__info {
    flex: 1;
}

.cookie-category__name {
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: #111827;
}

.cookie-category__description {
    font-size: 0.875rem;
    color: #6b7280;
    line-height: 1.5;
}

.cookie-category__toggle {
    flex-shrink: 0;
}

.cookie-category__status {
    font-size: 0.75rem;
    color: #6b7280;
    font-weight: 500;
}

/* Toggle Switch Styles */
.toggle-switch {
    position: relative;
    display: inline-block;
    width: 48px;
    height: 24px;
}

.toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #d1d5db;
    transition: 0.3s;
    border-radius: 24px;
}

.toggle-slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: 0.3s;
    border-radius: 50%;
}

.toggle-switch input:checked + .toggle-slider {
    background-color: #2563eb;
}

.toggle-switch input:checked + .toggle-slider:before {
    transform: translateX(24px);
}

.toggle-switch input:focus + .toggle-slider {
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.2);
}

.cookie-details__actions {
    display: flex;
    gap: 0.75rem;
    margin-top: 1.5rem;
}

/* Dark Mode Support */
@media (prefers-color-scheme: dark) {
    .cookie-banner {
        background: #1f2937;
        border-top-color: #374151;
    }

    .cookie-banner__title,
    .cookie-details__title,
    .cookie-category__name {
        color: #f9fafb;
    }

    .cookie-banner__description,
    .cookie-category__description {
        color: #d1d5db;
    }

    .cookie-category {
        background: #111827;
    }

    .cookie-category__status {
        color: #9ca3af;
    }
}

/* Responsive Design */
@media (max-width: 640px) {
    .cookie-banner {
        padding: 1rem;
    }

    .cookie-banner__actions {
        flex-direction: column;
    }

    .cookie-banner__btn {
        width: 100%;
    }

    .cookie-category__header {
        flex-direction: column;
    }

    .cookie-details__actions {
        flex-direction: column;
    }

    .cookie-details__actions .btn {
        width: 100%;
    }
}

/* Accessibility - Screen Reader Only */
.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border-width: 0;
}
</style>
