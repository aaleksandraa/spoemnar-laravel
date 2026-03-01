<?php

namespace App\View\Components\Analytics;

use App\Services\Analytics\DataLayerService;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class DataLayerInit extends Component
{
    public function __construct(
        private DataLayerService $dataLayerService
    ) {}

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.analytics.data-layer-init', [
            'initialState' => $this->dataLayerService->getInitialState(),
        ]);
    }
}
