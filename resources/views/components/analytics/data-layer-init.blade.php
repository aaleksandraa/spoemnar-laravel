@php
    // Set page_title from the current page title if available
    $initialState['page_title'] = $initialState['page_title'] ?? '';
@endphp
<script>
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push({!! json_encode($initialState, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!});
    window.analyticsDebugMode = {{ config('analytics.gtm.debug_mode') ? 'true' : 'false' }};
</script>
