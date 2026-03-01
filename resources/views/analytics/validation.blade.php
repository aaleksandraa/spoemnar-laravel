@extends('layouts.app')

@section('title', 'Event Tracking Validation')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Event Tracking Validation</h1>
            <p class="text-gray-600">Test and validate all 12 event types. This page is only accessible in non-production environments.</p>

            @if(config('app.env') !== 'production')
                <div class="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                    <p class="text-sm text-yellow-800">
                        <strong>Environment:</strong> {{ config('app.env') }} |
                        <strong>Debug Mode:</strong> {{ config('analytics.gtm.debug_mode') ? 'Enabled' : 'Disabled' }}
                    </p>
                </div>
            @endif
        </div>

        <!-- Consent Status -->
        <div class="mb-8 p-6 bg-white rounded-lg shadow-md">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Consent Status</h2>
            <div id="consent-status" class="space-y-2">
                <p class="text-gray-600">Loading consent status...</p>
            </div>
            <div class="mt-4 flex gap-2">
                <button onclick="grantConsent()" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                    Grant Analytics Consent
                </button>
                <button onclick="revokeConsent()" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
                    Revoke Analytics Consent
                </button>
                <button onclick="clearConsent()" class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">
                    Clear Consent
                </button>
            </div>
        </div>

        <!-- Event List -->
        <div class="space-y-6">
            @foreach($events as $index => $event)
                <div class="p-6 bg-white rounded-lg shadow-md">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-gray-900">
                                {{ $index + 1 }}. {{ $event['name'] }}
                            </h3>
                            <p class="text-sm text-gray-600 mt-1">{{ $event['description'] }}</p>
                        </div>
                        <button
                            onclick="triggerEvent('{{ $event['name'] }}', {{ json_encode($event['parameters']) }})"
                            class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 whitespace-nowrap ml-4"
                        >
                            Trigger Event
                        </button>
                    </div>

                    <!-- Parameters -->
                    <div class="mt-4">
                        <h4 class="text-sm font-medium text-gray-700 mb-2">Parameters:</h4>
                        <div class="bg-gray-50 rounded p-4 overflow-x-auto">
                            <pre class="text-xs text-gray-800">{{ json_encode($event['parameters'], JSON_PRETTY_PRINT) }}</pre>
                        </div>
                    </div>

                    <!-- Event Log -->
                    <div id="log-{{ $event['name'] }}" class="mt-4 hidden">
                        <h4 class="text-sm font-medium text-gray-700 mb-2">Event Log:</h4>
                        <div class="bg-green-50 border border-green-200 rounded p-3">
                            <p class="text-sm text-green-800">Event triggered successfully! Check browser console and GTM Preview mode.</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Data Layer Viewer -->
        <div class="mt-8 p-6 bg-white rounded-lg shadow-md">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Data Layer Viewer</h2>
            <div class="flex gap-2 mb-4">
                <button onclick="refreshDataLayer()" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                    Refresh Data Layer
                </button>
                <button onclick="clearDataLayerView()" class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">
                    Clear View
                </button>
            </div>
            <div id="data-layer-content" class="bg-gray-50 rounded p-4 overflow-x-auto max-h-96 overflow-y-auto">
                <pre class="text-xs text-gray-800">Loading data layer...</pre>
            </div>
        </div>

        <!-- Instructions -->
        <div class="mt-8 p-6 bg-blue-50 border border-blue-200 rounded-lg">
            <h2 class="text-xl font-semibold text-blue-900 mb-4">Testing Instructions</h2>
            <ol class="list-decimal list-inside space-y-2 text-sm text-blue-800">
                <li>Ensure analytics consent is granted (use the buttons above)</li>
                <li>Open browser DevTools (F12) and go to Console tab</li>
                <li>Enable GTM Preview mode (if testing GTM integration)</li>
                <li>Open GA4 DebugView (if testing GA4 integration)</li>
                <li>Click "Trigger Event" buttons to test each event type</li>
                <li>Verify events appear in:
                    <ul class="list-disc list-inside ml-6 mt-1">
                        <li>Browser console (if debug mode enabled)</li>
                        <li>GTM Preview mode (Tag Assistant)</li>
                        <li>GA4 DebugView</li>
                        <li>Data Layer Viewer below</li>
                    </ul>
                </li>
                <li>Test consent blocking by revoking consent and triggering events</li>
                <li>Verify blocked events show "consent_denied" in console</li>
            </ol>
        </div>

        <!-- Links -->
        <div class="mt-8 p-6 bg-gray-50 rounded-lg">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Useful Links</h2>
            <ul class="space-y-2 text-sm">
                <li>
                    <a href="https://tagassistant.google.com/" target="_blank" class="text-blue-600 hover:underline">
                        GTM Tag Assistant
                    </a>
                </li>
                <li>
                    <a href="https://analytics.google.com/" target="_blank" class="text-blue-600 hover:underline">
                        Google Analytics (DebugView)
                    </a>
                </li>
                <li>
                    <a href="{{ asset('docs/event-tracking-reference.md') }}" class="text-blue-600 hover:underline">
                        Event Tracking Reference
                    </a>
                </li>
                <li>
                    <a href="{{ asset('docs/troubleshooting-guide.md') }}" class="text-blue-600 hover:underline">
                        Troubleshooting Guide
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        updateConsentStatus();
        refreshDataLayer();
    });

    /**
     * Trigger an event
     */
    function triggerEvent(eventName, parameters) {
        if (!window.eventTracker) {
            alert('EventTracker not initialized. Make sure analytics JavaScript is loaded.');
            return;
        }

        // Get the tracking method name
        const methodMap = {
            'page_view': 'trackPageView',
            'view_memorial': 'trackMemorialView',
            'search': 'trackSearch',
            'form_submit': 'trackFormSubmit',
            'sign_up': 'trackSignUp',
            'create_memorial': 'trackMemorialCreation',
            'upload_media': 'trackMediaUpload',
            'submit_tribute': 'trackTributeSubmit',
            'navigation_click': 'trackNavigationClick',
            'outbound_click': 'trackOutboundClick',
            'file_download': 'trackFileDownload',
            'error_event': 'trackError'
        };

        const method = methodMap[eventName];

        if (method && typeof window.eventTracker[method] === 'function') {
            // Call the tracking method
            window.eventTracker[method](parameters);

            // Show success message
            const logElement = document.getElementById('log-' + eventName);
            if (logElement) {
                logElement.classList.remove('hidden');
                setTimeout(() => {
                    logElement.classList.add('hidden');
                }, 3000);
            }

            // Refresh data layer view
            setTimeout(refreshDataLayer, 100);
        } else {
            alert('Tracking method not found: ' + method);
        }
    }

    /**
     * Update consent status display
     */
    function updateConsentStatus() {
        const statusElement = document.getElementById('consent-status');

        if (!window.consentManager) {
            statusElement.innerHTML = '<p class="text-red-600">ConsentManager not initialized</p>';
            return;
        }

        const consent = window.consentManager.getConsent();

        if (!consent) {
            statusElement.innerHTML = `
                <p class="text-gray-600"><strong>Status:</strong> No consent stored</p>
                <p class="text-sm text-gray-500">Events will be blocked until consent is granted.</p>
            `;
            return;
        }

        const analyticsStatus = consent.analytics ?
            '<span class="text-green-600 font-semibold">Granted</span>' :
            '<span class="text-red-600 font-semibold">Denied</span>';

        const marketingStatus = consent.marketing ?
            '<span class="text-green-600 font-semibold">Granted</span>' :
            '<span class="text-red-600 font-semibold">Denied</span>';

        statusElement.innerHTML = `
            <p class="text-gray-800"><strong>Analytics Consent:</strong> ${analyticsStatus}</p>
            <p class="text-gray-800"><strong>Marketing Consent:</strong> ${marketingStatus}</p>
            <p class="text-sm text-gray-500 mt-2">
                Version: ${consent.version} |
                Timestamp: ${new Date(consent.timestamp).toLocaleString()}
            </p>
        `;
    }

    /**
     * Grant analytics consent
     */
    function grantConsent() {
        if (!window.consentManager) {
            alert('ConsentManager not initialized');
            return;
        }

        window.consentManager.saveConsent({
            analytics: true,
            marketing: false
        });

        updateConsentStatus();
        alert('Analytics consent granted! Events will now be tracked.');
    }

    /**
     * Revoke analytics consent
     */
    function revokeConsent() {
        if (!window.consentManager) {
            alert('ConsentManager not initialized');
            return;
        }

        window.consentManager.saveConsent({
            analytics: false,
            marketing: false
        });

        updateConsentStatus();
        alert('Analytics consent revoked! Events will be blocked.');
    }

    /**
     * Clear consent from localStorage
     */
    function clearConsent() {
        if (!window.consentManager) {
            alert('ConsentManager not initialized');
            return;
        }

        window.consentManager.deleteConsent();
        updateConsentStatus();
        alert('Consent cleared! Reload the page to see the cookie banner.');
    }

    /**
     * Refresh data layer viewer
     */
    function refreshDataLayer() {
        const contentElement = document.getElementById('data-layer-content');

        if (!window.dataLayer) {
            contentElement.innerHTML = '<pre class="text-xs text-red-600">dataLayer not found</pre>';
            return;
        }

        const dataLayerJson = JSON.stringify(window.dataLayer, null, 2);
        contentElement.innerHTML = `<pre class="text-xs text-gray-800">${dataLayerJson}</pre>`;
    }

    /**
     * Clear data layer view
     */
    function clearDataLayerView() {
        const contentElement = document.getElementById('data-layer-content');
        contentElement.innerHTML = '<pre class="text-xs text-gray-600">Data layer view cleared. Click "Refresh Data Layer" to reload.</pre>';
    }
</script>
@endsection
