---
name: theme-scaffold
description: Converts Figma design tokens into a frontend theme — generates theme.css (CSS custom properties), utilities.css (utility classes), and Blade component files. Triggers when the user asks to scaffold a theme, convert design tokens, generate CSS variables from Figma, export Figma variables, create a theme from colors or typography tokens, build a Blade UI kit, or mentions colors.json, typography.json, theme.css, utilities.css, ui-kit.blade.php, or design system output. Also triggers when the user says things like "generate my theme", "convert my Figma tokens", "create the CSS variables", or "scaffold the UI kit" — even without naming any specific file.
---

# Webreact theme scaffold

Read Figma design tokens and produce deterministic output files: `theme.css`, `utilities.css`, and individual Blade component files. The output is always the same for the same input — no creative decisions, no added tokens.

## Step 0 — get the tokens

Two valid entry points. Determine which applies before doing anything else:

**Path A — Figma MCP (preferred when available):** The user provides a Figma URL alongside the invocation (e.g. `/webreact:theme-scaffold https://figma.com/design/…`). If no URL was given and no JSON files were uploaded, ask for one:

> "Please share the Figma project URL so I can read the design tokens."

Once you have the URL, extract the `node-id` (e.g. `?node-id=1-2` → `1:2`; if no `node-id` is present in the URL, omit the `nodeId` argument and the MCP will use the currently selected node). Call `mcp__Figma__get_variable_defs` with that `nodeId`. It returns all variable collections for that file. Read every collection and proceed.

**Path B — uploaded JSON files:** The user uploads `colors.json`, `typography.json`, or both — with no Figma URL. Process every uploaded file directly. A URL is not required and should not be asked for.

If both a URL and JSON files are provided, prefer Path A.

Merge all collections into a single working set before generating output. Unknown token types are silently ignored unless the user asks about them.

## Color tokens

→ `references/colors.md`

## Typography tokens

→ `references/typography.md`

## Output files

→ `references/outputs.md`

## Working agreements

**Preserve token names exactly.** The CSS variable name comes directly from the token name — no renaming, no camelCase conversion, no opinion. `color/blue/400` becomes `--color-blue-400`; `font-size/xs` becomes `--font-size-xs`.

**CSS variables everywhere.** Utilities and Blade components reference variables — never hardcode a hex value or a pixel size outside of `theme.css`. A downstream project can reskin by overriding the variables; that only works if nothing is hardcoded.

**Output is deterministic.** Same tokens in, same files out, every run. Process tokens in the order they appear in the source; do not sort or reorder unless the user asks.

**Merge before generating.** When multiple files or collections are present, unify them first, then generate all output files in one pass.

**Ignore unknown token types silently.** If a token type is not `color`, `fontSizes`, `lineHeights`, `fontWeights`, `fontFamilies`, `letterSpacing`, or an alias to one of these, skip it without comment — unless the user explicitly asks what was skipped.

**Always show a list of planned output files before generating them.** After reading the tokens, present the exact list of files that will be written (e.g. `theme.css`, `utilities.css`, `resources/views/components/button.blade.php`, …) and wait for the user to confirm before writing anything.

**When the Figma MCP is not connected, the workflow is identical.** Process the uploaded files, generate the same output. There is nothing to mention about the MCP being absent.

## Commit

After writing all output files, stage them and commit with this exact message — no variation, no extra context:

```
feat: scaffold theme from Figma design tokens
```

Stage only the generated output files (`theme.css`, `utilities.css`, and the Blade component files). Do not stage anything else.

Always show the commit message to the user and wait for their explicit confirmation before running `git commit`.

## References

| File | Read it when |
|---|---|
| `references/colors.md` | Processing color tokens — from Figma variables or `colors.json` |
| `references/typography.md` | Processing typography tokens — font sizes, weights, families, line heights, px→rem conversion, hierarchy mapping |
| `references/outputs.md` | Generating `theme.css`, `utilities.css`, and the Blade component files — exact structure and conventions |
