<?php

namespace App\View\Components\SEO;

use App\Services\SEO\MetaTagService;
use Illuminate\View\Component;

class MetaTags extends Component
{
    public string $pageType;
    public array $context;
    public ?string $title;
    public ?string $description;
    public ?string $image;
    public ?string $canonicalUrl;
    public array $twitterTags;

    /**
     * Create a new component instance.
     */
    public function __construct(
        string $pageType = 'default',
        array $context = [],
        ?string $title = null,
        ?string $description = null,
        ?string $image = null
    ) {
        $metaService = app(MetaTagService::class);

        $this->pageType = $pageType;
        $this->context = $context;

        // Generate or use provided values
        $this->title = $title ?? config('app.name', 'Spomenar');
        $this->description = $description ?? $metaService->generateDescription($pageType, $context);
        $this->image = $metaService->getOgImage($image);
        $this->canonicalUrl = $metaService->getCanonicalUrl();
        $this->twitterTags = $metaService->getTwitterCardTags($this->title, $this->description, $this->image);
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render()
    {
        return view('components.seo.meta-tags');
    }
}
