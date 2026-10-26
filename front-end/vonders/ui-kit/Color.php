<?php

/*
 * Project: Vonders (Radicle / WordPress)
 * Origineel pad: app/View/Components/UiKit/Color.php
 * Wat het doet: Component class voor de kleurstaal.
 */

namespace App\View\Components\UiKit;

use Illuminate\View\Component;

class Color extends Component
{
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(
        public string $value = '',
        public string $hex = '',
    ) {
        //
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.ui-kit.color');
    }
}
