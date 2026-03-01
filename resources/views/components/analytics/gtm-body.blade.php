@php
    $enabled = config('analytics.gtm.enabled', false) && config('app.env') !== 'local';
    $gtmId = config('app.env') === 'production'
        ? config('analytics.gtm.container_id')
        : (config('analytics.gtm.container_id') ?? config('analytics.gtm.container_id'));
@endphp
@if($enabled && $gtmId)
<!-- Google Tag Manager (noscript) -->
<noscript>
<iframe src="https://www.googletagmanager.com/ns.html?id={{ $gtmId }}"
height="0" width="0" style="display:none;visibility:hidden" title="Google Tag Manager"></iframe>
</noscript>
<!-- End Google Tag Manager (noscript) -->
@endif
