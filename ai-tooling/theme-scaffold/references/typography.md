# Typography tokens

## px → rem conversion

All pixel values are converted to rem. The base is always 16px:

```
rem = px / 16
```

Common values:

| px | rem |
|---|---|
| 10 | 0.625rem |
| 12 | 0.75rem |
| 14 | 0.875rem |
| 16 | 1rem |
| 18 | 1.125rem |
| 20 | 1.25rem |
| 24 | 1.5rem |
| 30 | 1.875rem |
| 36 | 2.25rem |
| 48 | 3rem |
| 60 | 3.75rem |
| 72 | 4.5rem |

Round to three decimal places at most. Never emit `px` in `theme.css` — convert everything.

## Token types and variable names

| Token type | CSS variable prefix | Example |
|---|---|---|
| Font size | `--font-size-` | `--font-size-xs` |
| Line height | `--line-height-` | `--line-height-tight` |
| Font weight | `--font-weight-` | `--font-weight-bold` |
| Font family | `--font-family-` | `--font-family-sans` |
| Letter spacing | `--letter-spacing-` | `--letter-spacing-wide` |

Strip the type prefix from the Figma/JSON token name when constructing the variable name. If the token name already starts with the type (e.g. `fontSize/xs`), drop it:

| Token name | CSS variable |
|---|---|
| `fontSize/xs` | `--font-size-xs` |
| `fontSize/2xl` | `--font-size-2xl` |
| `lineHeight/tight` | `--line-height-tight` |
| `fontWeight/bold` | `--font-weight-bold` |
| `fontFamily/sans` | `--font-family-sans` |

## Hierarchy mapping

After defining all `--font-size-*` variables, emit a hierarchy block that maps heading and text levels to those variables using `var()`:

```css
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
```

**Exact mapping algorithm** — follow these steps in order; do not improvise:

1. If the token set contains explicit hierarchy tokens (names containing `heading/h1`, `heading/h2`, etc.), use those directly and skip steps 2–5:
   ```css
   --h1: var(--font-size-heading-h1);
   ```

2. Sort all generated `--font-size-*` variables by their rem value, **descending** (largest first). Call this list `S`.

3. If `S` is empty, **omit the hierarchy block entirely** — do not emit `--h1` through `--xs`.

4. Map heading levels h1–h6 to indices 0–5 of `S`. If `S` has fewer than 6 entries, reuse the last (smallest) entry for any remaining heading levels.

5. Map `--body` to the entry in `S` whose rem value is closest to `1rem` (when tied, pick the larger). Map `--small` to the entry one position below `--body` in `S`, or `--body`'s entry if it is already the last. Map `--xs` to `S`'s last entry (smallest).

Example — 4 sizes available (`7xl`=4.5rem, `2xl`=1.5rem, `base`=1rem, `sm`=0.875rem):

```css
--h1:    var(--font-size-7xl);   /* S[0] */
--h2:    var(--font-size-2xl);   /* S[1] */
--h3:    var(--font-size-base);  /* S[2] */
--h4:    var(--font-size-sm);    /* S[3] */
--h5:    var(--font-size-sm);    /* S[3] — reused, no S[4] */
--h6:    var(--font-size-sm);    /* S[3] — reused, no S[5] */
--body:  var(--font-size-base);  /* closest to 1rem */
--small: var(--font-size-sm);    /* one step below body */
--xs:    var(--font-size-sm);    /* smallest */
```

## Reading from the Figma MCP

Typography variables from `mcp__Figma__get_variable_defs` appear alongside color variables in the same response. Look for variables whose names contain `fontSize`, `lineHeight`, `fontWeight`, `fontFamily`, or `letterSpacing`. Their values are numbers (for sizes) or strings (for families and weights). Convert numeric values from px to rem.

## Reading from typography.json

```json
{
  "xs":  { "value": "12",  "type": "fontSizes" },
  "sm":  { "value": "14",  "type": "fontSizes" },
  "base":{ "value": "16",  "type": "fontSizes" },
  "xl":  { "value": "20",  "type": "fontSizes" }
}
```

Produces:

```css
--font-size-xs:   0.75rem;
--font-size-sm:   0.875rem;
--font-size-base: 1rem;
--font-size-xl:   1.25rem;
```

## Output

All typography variables go in `theme.css` under `:root`, after color variables. The hierarchy block follows immediately after the last `--font-size-*` variable. See `references/outputs.md` for the exact file structure.
