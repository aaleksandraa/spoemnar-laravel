<?php

namespace App\View\Components;

use Illuminate\View\Component;

class LazyImage extends Component
{
    public string $src;
    public string $alt;
    public ?int $width;
    public ?int $height;
    public bool $lazy;
    public string $class;
    public ?string $srcset;

    /**
     * Create a new component instance.
     */
    public function __construct(
        string $src,
        string $alt,
        ?int $width = null,
        ?int $height = null,
        bool $lazy = true,
        string $class = '',
        ?string $srcset = null
    ) {
        $this->src = $src;
        $this->alt = $alt;
        $this->width = $width;
        $this->height = $height;
        $this->lazy = $lazy;
        $this->class = $class;
        $this->srcset = $srcset;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render()
    {
        return view('components.lazy-image');
    }
}
