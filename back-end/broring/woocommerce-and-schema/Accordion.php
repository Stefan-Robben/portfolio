<?php

/*
 * Project:  Broring (broring-radicle)
 * Origineel: app/View/Components/Accordion.php
 * Blade-component dat FAQ-items als FAQPage-schema aan de Yoast-graph toevoegt.
 */

namespace App\View\Components;

use App\Services\Schema\Builders\FaqPageBuilder;
use App\Services\Schema\FaqService;
use Illuminate\View\Component;

class Accordion extends Component
{
    protected FaqService $faqService;

    public function __construct(
        public string $title = '',
        public string $accentColor = 'purple',
        public array $items = [],
        public bool $isPreview = false,
    ) {
        $this->faqService = app(FaqService::class);

        add_filter('wpseo_schema_webpage', [$this, 'addFaqSchema'], 10, 1);
    }

    public function addFaqSchema(array $data): array
    {
        if (empty($this->items)) {
            return $data;
        }

        $faqEntries = $this->faqService->buildFaqEntries($this->items);

        $faqSchema = FaqPageBuilder::fromEntries($faqEntries);

        $data['mainEntity'] = array_merge(
            $data['mainEntity'] ?? [],
            [$faqSchema]
        );

        return $data;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.accordion');
    }
}
