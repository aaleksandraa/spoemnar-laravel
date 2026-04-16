@if($ga4Service->shouldLoadDirectScript())
<!-- Google Analytics 4 -->
<script async src="https://www.googletagmanager.com/gtag/js?id={{ $ga4Service->getMeasurementId() }}"></script>
<script>
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('js', new Date());
gtag('config', '{{ $ga4Service->getMeasurementId() }}', {
    send_page_view: {{ $ga4Service->shouldSendInitialPageView() ? 'true' : 'false' }}
});
</script>
<!-- End Google Analytics 4 -->
@endif

