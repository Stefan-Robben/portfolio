# Build → Parcel, and Tailwind/Sass → plain CSS

Goal: Parcel bundles plain `resources/styles/app.css` + `resources/scripts/app.js` into
`dist/`, with **no Sass, Tailwind or PostCSS**. Parcel's default CSS transformer
(lightningcss) does minification, vendor-prefixing (per `browserslist`) and native-nesting
downleveling on its own — provided you do **not** add a `.postcssrc`.

## package.json

Target names must not collide with real package.json fields (a target named `scripts`
breaks the build — see SKILL gotcha 1). Use `css`/`js`. Keep the script names
`build`/`prod`/`start`/`dev` if a makefile/CI calls them.

```jsonc
{
  "name": "<theme>",
  "private": true,
  "scripts": {
    "clean": "rimraf dist .parcel-cache",
    "dev": "npm run clean && parcel build --no-optimize",
    "prod": "npm run clean && parcel build --no-source-maps",
    "start": "npm run clean && parcel watch",
    "build": "npm run prod"
  },
  "targets": {
    "css": { "source": "resources/styles/app.css", "distDir": "dist/styles", "context": "browser" },
    "js":  { "source": "resources/scripts/app.js", "distDir": "dist/scripts", "context": "browser", "outputFormat": "global" }
  },
  "alias": { "jquery": { "global": "jQuery" } },
  "browserslist": ["defaults"],
  "devDependencies": { "parcel": "^2.12.0", "prettier": "^2.7.1", "rimraf": "^3.0.2" }
}
```

No `dependencies` key. Removed: `laravel-mix`, `@webreact/*`, `browser-sync*`,
`concurrently`, `sass`, `sass-loader`, `resolve-url-loader`, `@babel/preset-env`, `core-js`,
`dotenv`, `tailwindcss`, `autoprefixer`, `postcss`. jQuery stays external via the `alias`
global + the `['jquery']` enqueue dep (Parcel has no webpack-style `externals`). Delete
`package-lock.json` and reinstall so the lockfile carries none of the old deps.

**Delete**: `tailwind.config.js`, `webpack.mix.js`, `webreact.config.js`, `babel.config.js`
(a Babel config forces Parcel off its fast SWC path). Do **not** create `.postcssrc`.

## includes/enqueue.php

Parcel doesn't content-hash *entry* bundles, so filenames are stable — cache-bust with
`md5_file()`, guard with `file_exists`, single CSS + single JS (collapse any legacy
modern/es5/es6 dual-build):

```php
<?php
namespace App;

function wr_enqueue_styles()
{
    $style = get_theme_file_path('dist/styles/app.css');
    if (file_exists($style)) {
        wp_enqueue_style('<theme>', get_theme_file_uri('dist/styles/app.css'), [], md5_file($style));
    }
    $script = get_theme_file_path('dist/scripts/app.js');
    if (file_exists($script)) {
        wp_enqueue_script('<theme>', get_theme_file_uri('dist/scripts/app.js'), ['jquery'], md5_file($script), true);
    }
}
add_action('wp_enqueue_scripts', 'App\wr_enqueue_styles');
```

## Style tree → plain CSS

`resources/styles/app.scss` (an `@use`/`@forward` barrel tree) becomes plain
`resources/styles/app.css` — an **`@import` list only** (imports must precede any
declaration); lightningcss inlines local `@import`s into the single `dist/styles/app.css`:

```css
@import "abstracts/variables/colors.css";
@import "components/buttons/base.css";
/* …one @import per former partial, in the old @forward order… */
@import "layout/footer.css";
```

Rename each partial `.scss` → `.css` (drop the leading underscore). Per file:

- **`@apply <utils>` → literal declarations** (mapping table below).
- **`#{!important}` → `!important`**, **`// comment` → `/* comment */`**.
- **Keep native CSS nesting** (`&:hover`, `&::marker`, nested `.child {}`) — lightningcss
  downlevels it. Far less churn than flattening by hand.
- **Preserve Salient-overriding selectors and every `!important` verbatim.** High-specificity
  selectors like `body #footer-outer #copyright.row p` exist to beat the parent theme; do not
  "simplify" them or overrides regress. The footer/header files are where careless edits break
  things.
- Files that are already plain CSS (e.g. a `:root{}` variables file, a `.button{--spacing:…}`
  file) → rename only.
- Custom Tailwind theme tokens: inline as literals (e.g. a `light-blue` color → `#0090f0`).
  Drop unused `theme.extend` tokens.
- Delete the `base/` Tailwind-injection subtree (`@tailwind` directives), the `_index.scss`
  barrels, and empty page partials.

When the markup is being rewritten anyway (the Blade→PHP view), replace its utility classes
with semantic component classes (`.employees__grid { … }`) and drop dead/typo classes.

### Utility → CSS mapping (Tailwind v3 defaults)

| Utility | CSS |
|---|---|
| `grid` / `flex` / `block` / `inline-block` / `hidden` | `display:grid` / `flex` / `block` / `inline-block` / `none` |
| `grid-cols-3` | `grid-template-columns:repeat(3,minmax(0,1fr))` |
| `gap-4` | `gap:1rem` |
| `flex-col` | `flex-direction:column` |
| `justify-between` | `justify-content:space-between` |
| `static`/`relative`/`absolute` | `position:static`/`relative`/`absolute` |
| `inset-0` | `inset:0` |
| `float-none` | `float:none` |
| `overflow-hidden` | `overflow:hidden` |
| `text-center` | `text-align:center` |
| `font-semibold` | `font-weight:600` |
| `text-black`/`text-white` | `color:#000`/`#fff` |
| `text-xs` | `font-size:.75rem;line-height:1rem` |
| `text-2xl` | `font-size:1.5rem;line-height:2rem` |
| `m-0`/`mt-0`/`ml-0`/`p-0` | `margin:0`/`margin-top:0`/`margin-left:0`/`padding:0` |
| `mx-auto` | `margin-left:auto;margin-right:auto` |
| `mb-2.5`/`mb-4`/`mb-8` | `margin-bottom:.625rem`/`1rem`/`2rem` |
| `pl-1`/`pl-4` | `padding-left:.25rem`/`1rem` |
| `w-full`/`w-auto`/`h-full` | `width:100%`/`width:auto`/`height:100%` |
| `h-20`/`h-32`/`w-48`/`h-48` | `height:5rem`/`8rem`; `width`/`height:12rem` |
| `max-w-[75%]` | `max-width:75%` |
| `border`/`border-l` | `border-width:1px`/`border-left-width:1px` |
| `border-solid`/`border-hidden` | `border-style:solid`/`hidden` |
| `border-white`/`border-[#hex]` | `border-color:#fff`/`#hex` |
| `rounded-full`/`rounded-md` | `border-radius:9999px`/`.375rem` |

Spacing scale: Tailwind unit `n` = `n * 0.25rem` (`4`→1rem, `2.5`→.625rem, `8`→2rem). Sizes
`20`/`32`/`48` → `5rem`/`8rem`/`12rem`. `text-xs`/`text-2xl` carry a line-height; when
`@apply`'d with `!important`, both declarations get `!important`.

## Verify the compiled CSS

`npm run prod`, then read `dist/styles/app.css` and confirm: nesting flattened correctly to
the right descendant selectors, every `!important` preserved, custom-property blocks intact,
and any icon-font `content:'\fXXX'` glyph survived (lightningcss emits the literal PUA char —
check it's present, e.g. `python3 -c "print([hex(ord(c)) for c in open('dist/styles/app.css').read() if ord(c)>=0xf000])"`).
