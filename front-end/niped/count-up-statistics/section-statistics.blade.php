{{--
  Project: Niped (Radicle / WordPress)
  Origineel pad: resources/views/components/section-statistics.blade.php
  Wat het doet: Anonieme Blade component voor een rij kerncijfers; geeft het aantal items door als CSS custom property.
--}}
@props([
    'items' => [], // array of ['value' => string, 'label' => string]
])

@php
    $items = collect($items)->filter();
    $hasItems = $items->isNotEmpty();
@endphp

@if($hasItems)
    <section {{ $attributes->class(['section-statistics'])->merge(['style' => '--section-statistics-items: ' . $items->count() . ';']) }}>
        <div class="section-statistics__container">
            @foreach($items as $item)
                <div class="section-statistics__item">
                    @if(filled($item['value'] ?? null))
                        <span class="section-statistics__value">{{ $item['value'] }}</span>
                    @endif

                    @if(filled($item['label'] ?? null))
                        <p class="section-statistics__label">{{ $item['label'] }}</p>
                    @endif
                </div>
            @endforeach
        </div>
    </section>
@endif
