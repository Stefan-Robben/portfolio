<?php

/*
 * Project: Niped (Radicle / WordPress)
 * Origineel pad: app/View/Components/Chart.php
 * Wat het doet: Blade component class voor de grafiek: valideert type en reeksen, levert de Chart.js-config en formatteert waarden voor de toegankelijke tabel.
 */

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Chart extends Component
{
    private const TYPES = ['bar', 'horizontal-bar', 'line', 'pie', 'doughnut'];

    /**
     * @var array<int, array{label: string, values: array<int, float|null>}>
     */
    public array $datasets;

    /**
     * Create a new component instance.
     *
     * @param  array<int, string>  $labels
     * @param  array<int, array{label: string, values: array<int, float|null>}>  $datasets
     */
    public function __construct(
        public string $type = 'bar', // bar, horizontal-bar, line, pie, doughnut
        public array $labels = [],
        array $datasets = [],
        public ?string $title = null,
        public ?string $intro = null,
        public ?string $unit = null, // appended to values, e.g. "%"
        public ?array $link = null, // ACF link array: url, title, target
        public int $headingLevel = 3,
    ) {
        $this->type = in_array($type, self::TYPES, true) ? $type : 'bar';

        // A pie shows parts of one whole: only the first series fits.
        $this->datasets = $this->isCircular() ? array_slice($datasets, 0, 1) : array_values($datasets);
    }

    public function shouldRender(): bool
    {
        return $this->labels !== [] && $this->datasets !== [];
    }

    public function isCircular(): bool
    {
        return in_array($this->type, ['pie', 'doughnut'], true);
    }

    /**
     * What Chart.js needs; styling is added in the browser from the theme.
     */
    public function config(): array
    {
        return [
            'type' => $this->type,
            'labels' => $this->labels,
            'datasets' => $this->datasets,
            'unit' => (string) $this->unit,
        ];
    }

    /**
     * "52%", "17.500 €"-style values for the data table.
     */
    public function formatValue(?float $value): string
    {
        if ($value === null) {
            return '–';
        }

        $number = number_format_i18n($value, floor($value) === $value ? 0 : 1);

        return match ($this->unit) {
            null, '' => $number,
            '%' => "{$number}%",
            '€' => "€ {$number}",
            default => "{$number} {$this->unit}",
        };
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.chart');
    }
}
