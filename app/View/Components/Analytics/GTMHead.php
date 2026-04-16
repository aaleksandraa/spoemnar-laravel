<?php

namespace App\View\Components\Analytics;

use App\Services\Analytics\GTMService;
use Illuminate\View\Component;
use Illuminate\View\View;

class GTMHead extends Component
{
    public function __construct(
        public GTMService $gtmService
    ) {}

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View
    {
        return view('components.analytics.gtm-head', [
            'gtmService' => $this->gtmService,
        ]);
    }
}
