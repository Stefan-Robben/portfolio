{{--
  Project: Niped (Radicle / WordPress)
  Origineel pad: resources/views/components/chart.blade.php
  Wat het doet: Markup van de grafiek: canvas voor Chart.js plus een sr-only datatabel met dezelfde cijfers voor screenreaders.
--}}
@php
    $label = filled($title) ? $title : __('Grafiek', 'radicle');
@endphp

<figure {{ $attributes->class(['chart', "chart--{$type}"]) }}>
    @if (filled($title) || filled($intro))
        <div class="chart__header">
            @if (filled($title))
                <x-heading :level="$headingLevel" class="chart__title">{{ $title }}</x-heading>
            @endif

            @if (filled($intro))
                <p class="chart__intro">{{ $intro }}</p>
            @endif
        </div>
    @endif

    {{-- Drawn by resources/js/components/chart.ts once it nears the viewport.
         The canvas is decorative for assistive tech; the table below carries
         the same numbers. --}}
    <div class="chart__canvas" data-chart="{{ json_encode($config()) }}">
        <canvas aria-hidden="true"></canvas>
    </div>

    <table class="sr-only">
        <caption>{{ $label }}</caption>
        <thead>
            <tr>
                <th scope="col">{{ __('Categorie', 'radicle') }}</th>
                @foreach ($datasets as $dataset)
                    <th scope="col">{{ $dataset['label'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($labels as $index => $rowLabel)
                <tr>
                    <th scope="row">{{ $rowLabel }}</th>
                    @foreach ($datasets as $dataset)
                        <td>{{ $formatValue($dataset['values'][$index] ?? null) }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>

    @if (filled($link['url'] ?? null))
        <x-button
            :href="$link['url']"
            :target="$link['target'] ?: null"
            color="pink"
            :has-icon="true"
            class="chart__button"
        >
            {{ $link['title'] ?: __('Lees meer', 'radicle') }}
        </x-button>
    @endif
</figure>
