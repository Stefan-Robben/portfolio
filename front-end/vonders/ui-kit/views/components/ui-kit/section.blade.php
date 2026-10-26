{{--
  Project: Vonders (Radicle / WordPress)
  Origineel pad: resources/views/components/ui-kit/section.blade.php
  Wat het doet: Subsectie binnen een UI-kit hoofdstuk.
--}}
<div  {{ $attributes->class([
    "uikit__section"
    ])->merge() }}>
    <header class="uikit__section-header">
        @if($title)
            <h3 class="uikit__section-title">
                {{ $title }}
            </h3>
        @endif

        @if($description)
            <div class="uikit__section-description">
                {{ $description }}
            </div>
        @endif
    </header>

    <div class="uikit__section-content">
        {{ $slot }}
    </div>
</div>
