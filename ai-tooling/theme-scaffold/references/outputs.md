# Output files

Generate all three files in every run. Never skip one because tokens for it are absent — emit the file with what is available, even if that means only a few variables.

---

## theme.css

All CSS custom properties under `:root`, wrapped in `@layer theme`. Order: colors first, then typography (font sizes, hierarchy, line heights, font weights, font families, letter spacing).

```css
@layer theme {
  :root {
    /* Colors */
    --color-blue: #382279;
    --color-blue-400: #57589D;
    --color-neutral-100: #f5f5f5;
    --color-neutral-900: #111111;
    --color-white: #ffffff;
    --color-brand-primary: var(--color-blue);

    /* Font sizes */
    --font-size-xs:   0.75rem;
    --font-size-sm:   0.875rem;
    --font-size-base: 1rem;
    --font-size-lg:   1.125rem;
    --font-size-xl:   1.25rem;
    --font-size-2xl:  1.5rem;
    --font-size-3xl:  1.875rem;
    --font-size-4xl:  2.25rem;
    --font-size-5xl:  3rem;
    --font-size-6xl:  3.75rem;
    --font-size-7xl:  4.5rem;

    /* Hierarchy */
    --h1: var(--font-size-7xl);
    --h2: var(--font-size-5xl);
    --h3: var(--font-size-4xl);
    --h4: var(--font-size-3xl);
    --h5: var(--font-size-2xl);
    --h6: var(--font-size-xl);
    --body: var(--font-size-base);
    --small: var(--font-size-sm);
    --xs: var(--font-size-xs);

    /* Line heights */
    --line-height-none:    1;
    --line-height-tight:   1.25;
    --line-height-snug:    1.375;
    --line-height-normal:  1.5;
    --line-height-relaxed: 1.625;
    --line-height-loose:   2;

    /* Font weights */
    --font-weight-light:    300;
    --font-weight-regular:  400;
    --font-weight-medium:   500;
    --font-weight-semibold: 600;
    --font-weight-bold:     700;

    /* Font families */
    --font-family-sans:  'Inter', sans-serif;
    --font-family-serif: 'Georgia', serif;
    --font-family-mono:  'JetBrains Mono', monospace;

    /* Letter spacing */
    --letter-spacing-tight:  -0.025em;
    --letter-spacing-normal:  0em;
    --letter-spacing-wide:    0.025em;
    --letter-spacing-wider:   0.05em;
    --letter-spacing-widest:  0.1em;
  }
}
```

Omit sections for which no tokens exist (e.g. if there are no line-height tokens, omit that block and its comment).

---

## utilities.css

Utility classes in `@layer utilities`. One class per token, each referencing the CSS variable — never a hardcoded value. Order: text color, background color, font size, font weight. Add more groups if the token set includes them (e.g. border-color).

```css
@layer utilities {
  /* Text colors */
  .text-blue         { color: var(--color-blue); }
  .text-blue-400     { color: var(--color-blue-400); }
  .text-neutral-100  { color: var(--color-neutral-100); }
  .text-neutral-900  { color: var(--color-neutral-900); }
  .text-white        { color: var(--color-white); }
  .text-brand        { color: var(--color-brand-primary); }

  /* Background colors */
  .bg-blue           { background-color: var(--color-blue); }
  .bg-blue-400       { background-color: var(--color-blue-400); }
  .bg-neutral-100    { background-color: var(--color-neutral-100); }
  .bg-neutral-900    { background-color: var(--color-neutral-900); }
  .bg-white          { background-color: var(--color-white); }
  .bg-brand          { background-color: var(--color-brand-primary); }

  /* Font sizes */
  .text-xs   { font-size: var(--font-size-xs); }
  .text-sm   { font-size: var(--font-size-sm); }
  .text-base { font-size: var(--font-size-base); }
  .text-lg   { font-size: var(--font-size-lg); }
  .text-xl   { font-size: var(--font-size-xl); }
  .text-2xl  { font-size: var(--font-size-2xl); }
  .text-3xl  { font-size: var(--font-size-3xl); }
  .text-4xl  { font-size: var(--font-size-4xl); }
  .text-5xl  { font-size: var(--font-size-5xl); }
  .text-6xl  { font-size: var(--font-size-6xl); }
  .text-7xl  { font-size: var(--font-size-7xl); }

  /* Font weights */
  .font-light    { font-weight: var(--font-weight-light); }
  .font-regular  { font-weight: var(--font-weight-regular); }
  .font-medium   { font-weight: var(--font-weight-medium); }
  .font-semibold { font-weight: var(--font-weight-semibold); }
  .font-bold     { font-weight: var(--font-weight-bold); }
}
```

The class name mirrors the token name — strip the `--color-` or `--font-size-` prefix and use the remainder as the suffix. For color utilities, generate both `.text-*` and `.bg-*` for every color token.

---

## Blade components

Laravel anonymous components are resolved by filename: `<x-button>` maps to `resources/views/components/button.blade.php`. A single file cannot define multiple components. Generate one file per component.

Files to generate, always in this set:

| File | Tag |
|---|---|
| `resources/views/components/button.blade.php` | `<x-button>` |
| `resources/views/components/badge.blade.php` | `<x-badge>` |
| `resources/views/components/heading.blade.php` | `<x-heading>` |
| `resources/views/components/text.blade.php` | `<x-text>` |

Use CSS variables from `theme.css` via inline styles. Never hardcode a hex or px value.

Adapt color and font variable references to match what was actually generated — if `--color-brand-primary` does not exist in the token set, substitute the first available color variable. The four-file structure is always the same.

---

### `resources/views/components/button.blade.php`

```blade
{{-- Generated by webreact-theme-scaffold. Do not edit by hand. --}}
{{-- Usage: <x-button>Label</x-button>                           --}}
{{--        <x-button variant="secondary">Label</x-button>       --}}
{{--        <x-button variant="ghost">Label</x-button>           --}}
@props(['variant' => 'primary'])

@php
$styles = match($variant) {
    'secondary' => 'background-color: transparent; color: var(--color-brand-primary); border: 1px solid var(--color-brand-primary);',
    'ghost'     => 'background-color: transparent; color: var(--color-brand-primary); border: none;',
    default     => 'background-color: var(--color-brand-primary); color: var(--color-white); border: none;',
};
@endphp

<button
    style="{{ $styles }} font-family: var(--font-family-sans); font-size: var(--font-size-base); font-weight: var(--font-weight-medium); padding: 0.5rem 1rem; border-radius: 0.375rem; cursor: pointer; line-height: var(--line-height-normal);"
    {{ $attributes }}
>{{ $slot }}</button>
```

---

### `resources/views/components/badge.blade.php`

```blade
{{-- Generated by webreact-theme-scaffold. Do not edit by hand. --}}
{{-- Usage: <x-badge>New</x-badge>                               --}}
{{--        <x-badge color="neutral">Draft</x-badge>             --}}
@props(['color' => 'brand'])

@php
$bg  = "var(--color-{$color}-100, var(--color-{$color}))";
$txt = "var(--color-{$color}-900, var(--color-white))";
@endphp

<span
    style="background-color: {{ $bg }}; color: {{ $txt }}; font-family: var(--font-family-sans); font-size: var(--font-size-xs); font-weight: var(--font-weight-medium); padding: 0.125rem 0.5rem; border-radius: 9999px; letter-spacing: var(--letter-spacing-wide);"
    {{ $attributes }}
>{{ $slot }}</span>
```

---

### `resources/views/components/heading.blade.php`

```blade
{{-- Generated by webreact-theme-scaffold. Do not edit by hand. --}}
{{-- Usage: <x-heading level="1">Page title</x-heading>         --}}
@props(['level' => '1'])

@php
$tag = 'h' . $level;
@endphp

<{{ $tag }}
    style="font-size: var(--h{{ $level }}); font-family: var(--font-family-sans); font-weight: var(--font-weight-bold); line-height: var(--line-height-tight); color: inherit;"
    {{ $attributes }}
>{{ $slot }}</{{ $tag }}>
```

---

### `resources/views/components/text.blade.php`

```blade
{{-- Generated by webreact-theme-scaffold. Do not edit by hand. --}}
{{-- Usage: <x-text>Body copy</x-text>                          --}}
{{--        <x-text size="small">Caption</x-text>               --}}
{{--        <x-text size="xs">Fine print</x-text>               --}}
@props(['size' => 'body'])

@php
$fontSize = match($size) {
    'small' => 'var(--small)',
    'xs'    => 'var(--xs)',
    default => 'var(--body)',
};
@endphp

<p
    style="font-size: {{ $fontSize }}; font-family: var(--font-family-sans); font-weight: var(--font-weight-regular); line-height: var(--line-height-normal); color: inherit;"
    {{ $attributes }}
>{{ $slot }}</p>
```
