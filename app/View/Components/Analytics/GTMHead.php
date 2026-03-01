<?php

namespace App\View\Components\Analytics;

use App\Services\Analytics\GTMService;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class GTMHead extends Component
{
    public function __construct(
        private GTMService $gtmService
    ) {}

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.analytics.gtm-head', [
            'gtmService' => $this->gtmService,
        ]);
    }
}
