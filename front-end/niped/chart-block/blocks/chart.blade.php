{{--
  Project: Niped (Radicle / WordPress)
  Origineel pad: resources/views/blocks/chart.blade.php
  Wat het doet: Gutenberg/ACF-blok template dat de <x-chart> component vult.
--}}
@if ($data['datasets'])
    <x-chart
        :id="$anchor"
        :type="$type"
        :title="$title"
        :intro="$intro"
        :labels="$data['labels']"
        :datasets="$data['datasets']"
        :unit="$unit"
        :link="$link"
    />
@elseif ($isPreview)
    {{ __('Plak of typ de gegevens van de grafiek in het veld "Gegevens". Dit kan in de sidebar of in de "block edit mode".', 'radicle') }}
@endif
