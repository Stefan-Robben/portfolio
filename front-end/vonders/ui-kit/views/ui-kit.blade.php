{{--
  Project: Vonders (Radicle / WordPress)
  Origineel pad: resources/views/ui-kit.blade.php
  Wat het doet: Page template 'UI Kit': levende styleguide met typografie, kleuren en knopvarianten, opgebouwd uit de echte Blade components.
--}}
@php
    /* Template name: UI Kit */
use Carbon\Carbon
@endphp

@extends('layouts.app')

@section('content')
    <div class="container space-y-8">
        <x-ui-kit>
            <x-slot:title>
                {{ __('Typografie hierarchië', 'webreact') }}
            </x-slot:title>
            <x-slot:description>
                {{ __('De typografische hiërarchie bepaalt welke schalen we standaard aan standaard HTML-koppen toewijzen. Let op: de kleur en de text-transform hangt af van de hiërarchie, niet van de tekstgrootte.', 'webreact') }}
            </x-slot:description>

            <x-ui-kit.section>
                <x-slot:title>
                    {{__('Headings (dark)', 'webreact')}}
                </x-slot:title>
                <x-slot:description>
                    {{__('Responsive schalen voor de standaard HTML headings, op basis van Tailwind breakpoints.', 'webreact')}}
                </x-slot:description>

                <h1 class="h1-xl">Heading 1 XL</h1>
                <h1>Heading 1</h1>
                <h2>Heading 2</h2>
                <h3>Heading 3</h3>
                <h4>Heading 4</h4>
                <h5>Heading 5</h5>
                <h6>Heading 6</h6>
            </x-ui-kit.section>

            <x-ui-kit.section>
                <x-slot:title>
                    {{__('Body (dark)', 'webreact')}}
                </x-slot:title>
                <x-slot:description>
                    {{__('Responsive schalen voor de standaard teksten, op basis van Tailwind breakpoints.', 'webreact')}}
                </x-slot:description>

                <div class="space-y-4">
                    <p class="text-lg lg:text-xl">Paragraph leading text</p>
                    <p>Paragraph text</p>
                    <p class="text-sm lg:text-base">Paragraph small text</p>
                </div>
            </x-ui-kit.section>
        </x-ui-kit>

        <x-ui-kit>
            <x-slot:title>
                {{ __('Kleuren', 'webreact') }}
            </x-slot:title>
            <x-slot:description>
                {{ __('Kleurenpalet is gebaseerd op de standaard benamingen en schalen van Tailwind, het front-end framework.', 'webreact') }}
            </x-slot:description>

            <x-ui-kit.section>
                <x-slot:title>
                    {{__('Brand', 'webreact')}}
                </x-slot:title>

                <div class="grid grid-cols-3 lg:grid-cols-6 gap-4 lg:gap-8">
                    <x-ui-kit.color value="Blue" hex="#382279"/>
                    <x-ui-kit.color value="Blue 400" hex="#57589D"/>
                    <x-ui-kit.color value="Blue 100" hex="#F0F1F9"/>
                    <x-ui-kit.color value="Blue 800" hex="#2D234D"/>

                    <x-ui-kit.color value="Purple" hex="#91127D"/>
                    <x-ui-kit.color value="Purple 100" hex="#F5EEF4"/>
                    <x-ui-kit.color value="Purple 400" hex="#B4017A"/>

                    <x-ui-kit.color value="Red" hex="#D70050"/>
                    <x-ui-kit.color value="Red 100" hex="#F5EAEE"/>
                </div>
            </x-ui-kit.section>

            <x-ui-kit.section>
                <x-slot:title>
                    {{__('Grayscale colors', 'webreact')}}
                </x-slot:title>

                <div class="grid grid-cols-3 lg:grid-cols-6 gap-4 lg:gap-8">
                    <x-ui-kit.color value="White" hex="#FFFFFF"/>
                    <x-ui-kit.color value="Gray 100" hex="#F3F4F6"/>
                    <x-ui-kit.color value="Gray 200" hex="#E5E7EB"/>
                    <x-ui-kit.color value="Gray 300" hex="#D1D5DB"/>
                    <x-ui-kit.color value="Gray 400" hex="#9CA3AF"/>
                    <x-ui-kit.color value="Gray 500" hex="#6B7280"/>
                    <x-ui-kit.color value="Gray 600" hex="#4B5563"/>
                    <x-ui-kit.color value="Gray 700" hex="#374151"/>
                    <x-ui-kit.color value="Gray 800" hex="#1F2937"/>
                    <x-ui-kit.color value="Gray 900" hex="#111827"/>
                    <x-ui-kit.color value="Black" hex="#030712"/>
                </div>
            </x-ui-kit.section>

            <x-ui-kit.section>
                <x-slot:title>
                    {{__('Other', 'webreact')}}
                </x-slot:title>

                <div class="grid grid-cols-3 lg:grid-cols-6 gap-4 lg:gap-8">
                    <x-ui-kit.color value="Green" hex="#227859"/>
                    <x-ui-kit.color value="Green 200" hex="#C1F7E4"/>
                </div>
            </x-ui-kit.section>

            <x-ui-kit.section>
                <x-slot:title>
                    {{__('Opaque', 'webreact')}}
                </x-slot:title>

                <div class="grid grid-cols-3 lg:grid-cols-6 gap-4 lg:gap-8">
                    <x-ui-kit.color value="White 60" hex="rgba(255, 255, 255, 0.6)"/>
                    <x-ui-kit.color value="White 80" hex="rgba(255, 255, 255, 0.8)"/>
                    <x-ui-kit.color value="Gray 600 60" hex="rgba(75, 85, 99, 0.6)"/>
                </div>
            </x-ui-kit.section>
        </x-ui-kit>

        <x-ui-kit>
            <x-slot:title>
                {{ __('Buttons', 'webreact') }}
            </x-slot:title>
            <x-slot:description>
                {{ __('De knoppen kunnen optioneel een icoon rechts bevatten. Alleen voor de meest primaire acties.', 'webreact') }}
            </x-slot:description>

            <x-ui-kit.section>
                <x-slot:title>
                    {{ __('Solid', 'webreact') }}
                </x-slot:title>

                <div class="flex flex-row flex-wrap gap-6 uikit__variants">
                    <x-button color="purple" size="regular" :has-icon="false" href="https://www.webreact.nl">
                        {{ __('Button', 'webreact') }}
                    </x-button>

                    <x-button color="red" size="regular" :has-icon="false" href="https://www.webreact.nl">
                        {{ __('Button', 'webreact') }}
                    </x-button>

                    <x-button color="blue" size="regular" :has-icon="false" href="https://www.webreact.nl">
                        {{ __('Button', 'webreact') }}
                    </x-button>

                    <x-button color="white" size="regular" :has-icon="false" href="https://www.webreact.nl">
                        {{ __('Button', 'webreact') }}
                    </x-button>

                    <x-button color="white" size="regular" :has-icon="false" element="button" disabled>
                        {{ __('Button', 'webreact') }}
                    </x-button>
                </div>
            </x-ui-kit.section>

            <x-ui-kit.section>
                <x-slot:title>
                    {{ __('Solid with icon', 'webreact') }}
                </x-slot:title>

                <div class="flex flex-row flex-wrap gap-6 uikit__variants">
                    <x-button color="purple" size="regular" :has-icon="true" href="https://www.webreact.nl">
                        {{ __('Button', 'webreact') }}
                    </x-button>

                    <x-button color="red" size="regular" :has-icon="true" href="https://www.webreact.nl">
                        {{ __('Button', 'webreact') }}
                    </x-button>

                    <x-button color="blue" size="regular" :has-icon="true" href="https://www.webreact.nl">
                        {{ __('Button', 'webreact') }}
                    </x-button>

                    <x-button color="white" size="regular" :has-icon="true" href="https://www.webreact.nl">
                        {{ __('Button', 'webreact') }}
                    </x-button>

                    <x-button color="white" size="regular" :has-icon="true" element="button" disabled>
                        {{ __('Button', 'webreact') }}
                    </x-button>
                </div>

                <div class="flex flex-row flex-wrap gap-6 uikit__variants">
                    <x-button color="purple" size="regular" :has-icon="true" href="#">
                        {{ __('Button', 'webreact') }}
                    </x-button>

                    <x-button color="red" size="regular" :has-icon="true" href="mailto:">
                        {{ __('Button', 'webreact') }}
                    </x-button>

                    <x-button color="blue" size="regular" :has-icon="true" href="tel:">
                        {{ __('Button', 'webreact') }}
                    </x-button>

                    <x-button color="white" size="regular" :has-icon="true" element="button" disabled>
                        {{ __('Button', 'webreact') }}
                    </x-button>
                </div>
            </x-ui-kit.section>
        </x-ui-kit>

        {{-- … (ingekort): de rest van de pagina toont op dezelfde manier text links, icon buttons,
             badges, formulieren, accordions, cards en andere componenten met voorbeeldcontent. --}}
    </div>
@endsection
