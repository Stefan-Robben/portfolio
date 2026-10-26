{{--
  Project: Vonders (Radicle / WordPress)
  Origineel pad: resources/views/components/ui-kit/color.blade.php
  Wat het doet: Kleurstaal met naam en hexwaarde.
--}}
<div class="uikit__color">
    <figure class="uikit__color-bg" style="background-color: {{$hex}}"></figure>
    <footer class="uikit__color-footer">
        <div class="uikit__color-footer-value">
            {{ $value }}
        </div>
        <div class="uikit__color-footer-hex">
            {{ $hex }}
        </div>
    </footer>
</div>
