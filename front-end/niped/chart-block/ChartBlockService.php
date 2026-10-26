<?php

/*
 * Project: Niped (Radicle / WordPress)
 * Origineel pad: app/Services/Meta/Blocks/ChartBlockService.php
 * Wat het doet: Leest de ACF-velden van het blok en parset een geplakte tabel (Excel/CSV, NL-getalnotatie) naar labels en reeksen.
 */

namespace App\Services\Meta\Blocks;

class ChartBlockService
{
    public function getType(): string
    {
        return get_field('chart_type') ?: 'bar';
    }

    public function getTitle(): string
    {
        return get_field('chart_title') ?: '';
    }

    public function getIntro(): string
    {
        return get_field('chart_intro') ?: '';
    }

    public function getUnit(): string
    {
        return get_field('chart_unit') ?: '';
    }

    public function getLink(): ?array
    {
        return get_field('chart_link') ?: null;
    }

    /**
     * @return array{labels: array<int, string>, datasets: array<int, array{label: string, values: array<int, float|null>}>}
     */
    public function getData(): array
    {
        return self::parse((string) get_field('chart_data'));
    }

    /**
     * Read a table pasted from a spreadsheet or typed as CSV: the first row
     * names the series (its first cell is ignored), every next row is a label
     * followed by one value per series. The separator is whichever of tab,
     * semicolon or comma the header uses; values may use a decimal comma.
     *
     * @return array{labels: array<int, string>, datasets: array<int, array{label: string, values: array<int, float|null>}>}
     */
    public static function parse(string $text): array
    {
        // Only line breaks are trimmed: a pasted header starts with a tab for
        // its empty first cell, and trimming that would shift every series.
        $lines = array_values(array_filter(
            array_map(fn (string $line) => rtrim($line, "\r\n"), preg_split('/\R/', trim($text, "\r\n")) ?: []),
            fn (string $line) => trim($line) !== '',
        ));

        $empty = ['labels' => [], 'datasets' => []];

        if (count($lines) < 2) {
            return $empty;
        }

        $separator = self::separator($lines[0]);
        $header = str_getcsv($lines[0], $separator, '"', '');
        $series = array_slice($header, 1);

        if ($series === []) {
            return $empty;
        }

        $labels = [];
        $values = array_fill(0, count($series), []);

        foreach (array_slice($lines, 1) as $line) {
            $cells = str_getcsv($line, $separator, '"', '');
            $label = trim((string) ($cells[0] ?? ''));

            if ($label === '') {
                continue;
            }

            $labels[] = $label;

            foreach ($series as $index => $name) {
                $values[$index][] = self::number($cells[$index + 1] ?? null);
            }
        }

        $datasets = [];

        foreach ($series as $index => $name) {
            // A series without a single number is a typo or an empty column.
            if (array_filter($values[$index], fn ($value) => $value !== null) === []) {
                continue;
            }

            $datasets[] = ['label' => trim($name), 'values' => $values[$index]];
        }

        return $datasets === [] ? $empty : ['labels' => $labels, 'datasets' => $datasets];
    }

    private static function separator(string $header): string
    {
        foreach (["\t", ';', ','] as $candidate) {
            if (str_contains($header, $candidate)) {
                return $candidate;
            }
        }

        return ',';
    }

    /**
     * "1.234,5", "1234.5", "52%" and "€ 17.500" all become numbers; anything
     * else is a gap in the series.
     */
    private static function number(?string $cell): ?float
    {
        $cell = preg_replace('/[^\d,.\-]/', '', (string) $cell);

        if ($cell === '' || $cell === '-') {
            return null;
        }

        // Both separators: the last one is the decimal mark.
        if (str_contains($cell, ',') && str_contains($cell, '.')) {
            $cell = strrpos($cell, ',') > strrpos($cell, '.')
                ? str_replace(['.', ','], ['', '.'], $cell)
                : str_replace(',', '', $cell);
        } elseif (str_contains($cell, ',')) {
            $cell = str_replace(',', '.', $cell);
        } elseif (preg_match('/^-?\d{1,3}(\.\d{3})+$/', $cell)) {
            // "17.500" is Dutch thousands, not seventeen and a half.
            $cell = str_replace('.', '', $cell);
        }

        return is_numeric($cell) ? (float) $cell : null;
    }
}
