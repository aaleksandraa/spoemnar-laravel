@php
    use App\Services\SEO\StructuredDataService;

    $service = app(StructuredDataService::class);
    $schema = null;

    switch ($type) {
        case 'organization':
            $schema = $service->generateOrganizationSchema();
            break;

        case 'website':
            $schema = $service->generateWebSiteSchema();
            break;

        case 'person':
            if ($data) {
                $schema = $service->generatePersonSchema($data);
            }
            break;

        case 'breadcrumb':
            if ($breadcrumbs) {
                $schema = $service->generateBreadcrumbSchema($breadcrumbs);
            }
            break;
    }

    $jsonLd = null;
    if ($schema && $service->validateSchema($schema)) {
        $jsonLd = $service->toJsonLd($schema);
    }
@endphp

@if($jsonLd)
<script type="application/ld+json">
{!! $jsonLd !!}
</script>
@endif
