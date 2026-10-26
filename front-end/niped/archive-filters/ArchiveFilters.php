<?php

/*
 * Project: Niped (Radicle / WordPress)
 * Origineel pad: app/View/Components/ArchiveFilters.php
 * Wat het doet: Component class voor het filterpaneel op archiefpagina's: bepaalt welke FacetWP-facets zichtbaar zijn en maakt unieke ids voor ARIA.
 */

namespace App\View\Components;

use App\Enums\Facet;
use App\Enums\PostType;
use App\Enums\Taxonomy;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use WP_Term;

class ArchiveFilters extends Component
{
    public string $panelId;

    /**
     * @var array<int, array{name: string, label: string, labelId: string}>
     */
    public array $facets;

    /**
     * @param  array<Facet>  $filters
     */
    public function __construct(
        array $filters = [],
        public ?Facet $search = Facet::Search, // facet next to the toggle, or null
        public int $ghosts = 6, // placeholder cards shown while FacetWP loads new results
    ) {
        $this->panelId = uniqid('archive-filters-panel-');

        $this->facets = array_map(fn (Facet $facet) => [
            'name' => $facet->value,
            'label' => config("facets.{$facet->value}.label"),
            'labelId' => "{$this->panelId}-{$facet->value}",
        ], $this->filters($filters));
    }

    /**
     * A sub-theme overview has nothing left to narrow down by sub-theme.
     *
     * @param  array<Facet>  $filters
     * @return array<Facet>
     */
    private function filters(array $filters): array
    {
        $filters = $filters ?: Facet::filters(PostType::tryFrom((string) get_query_var('post_type')));
        $term = get_queried_object();

        if ($term instanceof WP_Term && $term->taxonomy === Taxonomy::Category->value && $term->parent) {
            $filters = array_filter($filters, fn (Facet $facet) => $facet !== Facet::Subtheme);
        }

        return array_values($filters);
    }

    public function render(): View|Closure|string
    {
        return view('components.archive-filters');
    }
}
