{{--
  Project: Niped (Radicle / WordPress)
  Origineel pad: resources/views/components/archive-filters.blade.php
  Wat het doet: Uitklapbaar filterpaneel met Alpine.js (aria-expanded, aria-controls, Escape sluit) en ghost cards als laadstatus.
--}}
<div
    x-data="archiveFilters(@js(array_column($facets, 'name')))"
    x-on:keydown.escape="open = false"
    {{ $attributes->class(['archive-filters']) }}
>
    <div class="archive-filters__bar">
        <x-button
            type="button"
            variant="outlined"
            color="black"
            icon="fa/7/filter-list-regular-full"
            class="archive-filters__toggle"
            x-on:click="open = ! open"
            x-bind:aria-expanded="open"
            aria-controls="{{ $panelId }}"
        >
            {{ __('Filteren', 'radicle') }}<span x-cloak x-show="count > 0" x-text="` (${count})`"></span>
        </x-button>

        @if($search)
            <div class="archive-filters__search">
                <x-facet :name="$search->value" />
            </div>
        @endif
    </div>

    <div
        id="{{ $panelId }}"
        class="archive-filters__panel"
        x-cloak
        x-show="open"
        x-collapse
    >
        <div class="archive-filters__panel-inner">
            @foreach($facets as $facet)
                <div class="archive-filters__filter" role="group" aria-labelledby="{{ $facet['labelId'] }}">
                    <p id="{{ $facet['labelId'] }}" class="archive-filters__label">{{ $facet['label'] }}</p>
                    <x-facet :name="$facet['name']" />
                </div>
            @endforeach

            <button type="button" class="archive-filters__close" x-on:click="open = false">
                <x-icon name="fa/7/xmark-regular-full" class="archive-filters__close-icon" />
                <span class="sr-only">{{ __('Filters sluiten', 'radicle') }}</span>
            </button>
        </div>
    </div>

    @if($ghosts > 0)
        <template data-facetwp-ghosts>
            @for($i = 0; $i < $ghosts; $i++)
                <x-card
                    class="card--ghost"
                    title="Titel van een artikel"
                    :meta="['Onderwerp', 'Sector']"
                    aria-hidden="true"
                    inert
                />
            @endfor
        </template>
    @endif
</div>
