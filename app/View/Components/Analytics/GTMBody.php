<?php

namespace App\View\Components\Analytics;

use Illuminate\View\Component;
use Illuminate\View\View;

class GtmBody extends Component
{
    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View
    {
        return view('components.analytics.gtm-body');
    }
}
