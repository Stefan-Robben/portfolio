# Color tokens

## Reading from the Figma MCP

`mcp__Figma__get_variable_defs` returns variable collections. Color variables have a resolved value that is an RGBA object or a hex string. Aliases (one variable referencing another) must be resolved to their final hex value before generating CSS.

A typical Figma variable name uses `/` as a separator: `color/blue`, `color/blue/400`, `color/neutral/100`. Strip the leading `color/` segment and replace remaining `/` with `-` to get the CSS variable name:

| Figma variable | CSS variable |
|---|---|
| `color/blue` | `--color-blue` |
| `color/blue/400` | `--color-blue-400` |
| `color/neutral/100` | `--color-neutral-100` |
| `color/white` | `--color-white` |

## Reading from colors.json

Design token JSON uses a nested object structure. The path from root to leaf becomes the CSS variable name, joined with `-`:

```json
{
  "blue": {
    "value": "#382279",
    "type": "color"
  },
  "blue-400": {
    "value": "#57589D",
    "type": "color"
  }
}
```

Produces:

```css
--color-blue: #382279;
--color-blue-400: #57589D;
```

Nested groups follow the same rule — flatten the path:

```json
{
  "neutral": {
    "100": { "value": "#f5f5f5", "type": "color" },
    "900": { "value": "#111111", "type": "color" }
  }
}
```

Produces:

```css
--color-neutral-100: #f5f5f5;
--color-neutral-900: #111111;
```

## Aliases

When a token's value references another token (`{blue.400}` or `$blue-400`), resolve it to the actual hex value. Never emit a `var()` reference inside `theme.css` for a color alias — CSS variables referencing other CSS variables at the `:root` level work, but the simpler resolved value is preferred unless the alias is truly dynamic (e.g. a semantic token like `color/brand/primary` → `color/blue`).

For semantic color tokens (e.g. `color/brand/primary: color/blue`), emit a `var()` reference:

```css
--color-brand-primary: var(--color-blue);
```

## Output

All color variables go in `theme.css` under `:root`. See `references/outputs.md` for the exact file structure.

Example:

```css
--color-blue: #382279;
--color-blue-400: #57589D;
--color-neutral-100: #f5f5f5;
--color-neutral-900: #111111;
--color-white: #ffffff;
--color-brand-primary: var(--color-blue);
```
