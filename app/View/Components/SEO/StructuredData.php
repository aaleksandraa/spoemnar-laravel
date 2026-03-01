<?php

namespace App\View\Components\SEO;

use App\Services\SEO\StructuredDataService;
use Illuminate\View\Component;
use Illuminate\View\View;

class StructuredData extends Component
{
    public string $type;
    public mixed $data;
    public ?array $breadcrumbs;

    /**
     * Create a new component instance.
     *
     * @param string $type Schema type: 'organization', 'website', 'person', 'breadcrumb'
     * @param mixed $data Data for the schema (e.g., Memorial model for person schema)
     * @param array|null $breadcrumbs Breadcrumb items for breadcrumb schema
     */
    public function __construct(
        string $type = 'organization',
        mixed $data = null,
        ?array $breadcrumbs = null
    ) {
        $this->type = $type;
        $this->data = $data;
        $this->breadcrumbs = $breadcrumbs;
    }

    /**
     * Get the structured data JSON-LD
     *
     * @return string|null
     */
    public function getJsonLd(): ?string
    {
        $service = app(StructuredDataService::class);
        $schema = null;

        switch ($this->type) {
            case 'organization':
                $schema = $service->generateOrganizationSchema();
                break;

            case 'website':
                $schema = $service->generateWebSiteSchema();
                break;

            case 'person':
                if ($this->data) {
                    $schema = $service->generatePersonSchema($this->data);
                }
                break;

            case 'breadcrumb':
                if ($this->breadcrumbs) {
                    $schema = $service->generateBreadcrumbSchema($this->breadcrumbs);
                }
                break;
        }

        if ($schema && $service->validateSchema($schema)) {
            return $service->toJsonLd($schema);
        }

        return null;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return View
     */
    public function render(): View
    {
        return view('components.seo.structured-data');
    }
}
