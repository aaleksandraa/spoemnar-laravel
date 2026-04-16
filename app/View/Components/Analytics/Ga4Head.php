<?php

namespace App\View\Components\Analytics;

use App\Services\Analytics\GA4Service;
use Illuminate\View\Component;
use Illuminate\View\View;

class Ga4Head extends Component
{
    public function __construct(
        public GA4Service $ga4Service
    ) {}

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View
    {
        return view('components.analytics.ga4-head', [
            'ga4Service' => $this->ga4Service,
        ]);
    }
}
