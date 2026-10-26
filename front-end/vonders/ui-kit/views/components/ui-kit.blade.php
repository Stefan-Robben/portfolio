{{--
  Project: Vonders (Radicle / WordPress)
  Origineel pad: resources/views/components/ui-kit.blade.php
  Wat het doet: Wrapper-component voor een UI-kit hoofdstuk met titel- en beschrijving-slots.
--}}
<div {{ $attributes->class([
    "uikit",
    ])->merge() }}>
    <header class="uikit__header">
        @if($title)
            <h3 class="uikit__title">
                {{ $title }}
            </h3>
        @endif

        @if($description)
            <div class="uikit__description">
                {{ $description }}
            </div>
        @endif
    </header>

    <div class="uikit__content">
        {{ $slot }}
    </div>
</div>
