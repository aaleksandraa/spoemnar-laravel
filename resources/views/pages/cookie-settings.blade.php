@extends('layouts.app')

@section('title', __('cookies.settings_page.title'))

@section('content')
<div class="container mx-auto px-4 py-8 max-w-4xl">
    <div class="cookie-settings-page">
        {{-- Page Header --}}
        <header class="mb-8">
            <h1 class="text-3xl font-bold mb-4">
                {{ __('cookies.settings_page.title') }}
            </h1>
            <p class="text-gray-600 dark:text-gray-400">
                {{ __('cookies.settings_page.description') }}
            </p>
        </header>

        {{-- Success Message --}}
        <div id="success-message"
             class="hidden bg-green-100 dark:bg-green-900 border border-green-400 dark:border-green-700 text-green-700 dark:text-green-300 px-4 py-3 rounded mb-6"
             role="alert">
            <p>{{ __('cookies.settings_page.success_message') }}</p>
        </div>

        {{-- Current Preferences Section --}}
        <section class="mb-8 bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold mb-4">
                {{ __('cookies.settings_page.current_preferences') }}
            </h2>

            <div id="last-updated" class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                {{-- Will be populated by JavaScript --}}
            </div>

            {{-- Necessary Cookies --}}
            <div class="cookie-setting-item mb-6 pb-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex justify-between items-start gap-4">
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold mb-2">
                            {{ __('cookies.categories.necessary.title') }}
                        </h3>
                        <p class="text-gray-600 dark:text-gray-400 text-sm mb-3">
                            {{ __('cookies.categories.necessary.description') }}
                        </p>
                        <div class="text-xs text-gray-500 dark:text-gray-500">
                            <strong>{{ __('Cookies used:') }}</strong> session_id, csrf_token, locale
                        </div>
                    </div>
                    <div class="flex-shrink-0">
                        <span class="inline-block px-3 py-1 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded">
                            {{ __('cookies.always_active') }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Analytics Cookies --}}
            <div class="cookie-setting-item mb-6 pb-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex justify-between items-start gap-4">
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold mb-2">
                            {{ __('cookies.categories.analytics.title') }}
                        </h3>
                        <p class="text-gray-600 dark:text-gray-400 text-sm mb-3">
                            {{ __('cookies.categories.analytics.description') }}
                        </p>
                        <div class="text-xs text-gray-500 dark:text-gray-500">
                            <strong>{{ __('Cookies used:') }}</strong> _ga, _gid, _gat
                        </div>
                    </div>
                    <div class="flex-shrink-0">
                        <label class="toggle-switch">
                            <input type="checkbox"
                                   id="analytics-toggle"
                                   aria-label="{{ __('cookies.categories.analytics.title') }}">
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>
            </div>

            {{-- Marketing Cookies --}}
            <div class="cookie-setting-item">
                <div class="flex justify-between items-start gap-4">
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold mb-2">
                            {{ __('cookies.categories.marketing.title') }}
                        </h3>
                        <p class="text-gray-600 dark:text-gray-400 text-sm mb-3">
                            {{ __('cookies.categories.marketing.description') }}
                        </p>
                        <div class="text-xs text-gray-500 dark:text-gray-500">
                            <strong>{{ __('Cookies used:') }}</strong> _gcl_au, IDE, test_cookie
                        </div>
                    </div>
                    <div class="flex-shrink-0">
                        <label class="toggle-switch">
                            <input type="checkbox"
                                   id="marketing-toggle"
                                   aria-label="{{ __('cookies.categories.marketing.title') }}">
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>
            </div>
        </section>

        {{-- Save Button --}}
        <div class="flex justify-between items-center">
            <button type="button"
                    id="save-cookie-settings"
                    class="btn btn-primary px-6 py-3">
                {{ __('cookies.settings_page.save_changes') }}
            </button>

            <a href="{{ route('privacy', ['locale' => app()->getLocale()]) }}"
               class="text-blue-600 dark:text-blue-400 hover:underline">
                {{ __('cookies.banner.privacy_policy') }}
            </a>
        </div>
    </div>
</div>

<style>
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

/* Dark mode adjustments */
@media (prefers-color-scheme: dark) {
    .toggle-slider {
        background-color: #4b5563;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Get consent manager from window (initialized in app.js)
    const consentManager = window.consentManager;

    if (!consentManager) {
        console.error('ConsentManager not found');
        return;
    }

    // Get toggle elements
    const analyticsToggle = document.getElementById('analytics-toggle');
    const marketingToggle = document.getElementById('marketing-toggle');
    const saveButton = document.getElementById('save-cookie-settings');
    const successMessage = document.getElementById('success-message');
    const lastUpdatedElement = document.getElementById('last-updated');

    // Load current preferences
    function loadPreferences() {
        const consent = consentManager.getConsent();

        if (consent) {
            analyticsToggle.checked = consent.analytics || false;
            marketingToggle.checked = consent.marketing || false;

            // Display last updated date
            const date = new Date(consent.timestamp);
            const formattedDate = date.toLocaleDateString('{{ app()->getLocale() }}', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });

            lastUpdatedElement.textContent = '{{ __("cookies.settings_page.last_updated", ["date" => ""]) }}'.replace(':date', formattedDate);
        }
    }

    // Save preferences
    function savePreferences() {
        const preferences = {
            necessary: true,
            analytics: analyticsToggle.checked,
            marketing: marketingToggle.checked
        };

        const saved = consentManager.saveConsent(preferences);

        if (saved) {
            // Show success message
            successMessage.classList.remove('hidden');

            // Update last updated text
            loadPreferences();

            // Hide success message after 5 seconds
            setTimeout(() => {
                successMessage.classList.add('hidden');
            }, 5000);

            // Scroll to top to show success message
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    }

    // Initialize
    loadPreferences();

    // Attach event listener to save button
    saveButton.addEventListener('click', savePreferences);

    // Also save on toggle change for real-time updates
    analyticsToggle.addEventListener('change', savePreferences);
    marketingToggle.addEventListener('change', savePreferences);
});
</script>
@endsection
