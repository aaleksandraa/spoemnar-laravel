@php
    $jsonLd = $this->getJsonLd();
@endphp

@if($jsonLd)
<script type="application/ld+json">
{!! $jsonLd !!}
</script>
@endif
