---
name: bedrock-to-native-wp
description: >-
  Migrate an existing Roots Bedrock WordPress project (and its theme) to a plain
  native WordPress install with a Parcel.js build and hand-written CSS. Use this
  whenever the user wants to move a WordPress site off Bedrock, stop managing
  WordPress core/plugins with Composer, replace a WordPress theme's Laravel Mix /
  Bud / webpack build with Parcel, convert Blade templates in a WordPress theme to
  native PHP, strip Tailwind or Sass out of a WordPress theme down to plain CSS,
  make the repo track only the theme, or decouple a theme from the proprietary
  webreactor/framework stack (webreactor/* Composer packages, @webreact/* npm
  packages) or the Roots stack. It fits the webreact/boilerplate-theme especially
  (a Salient child theme driven by WPBakery/Visual Composer). Trigger on phrasings
  like "migrate off Bedrock", "go native WordPress", "drop Composer for plugins",
  "switch the theme build to Parcel", "remove Blade/Tailwind/Sass from the theme",
  "make it a theme-only git repo", or "get rid of the webreactor framework", even
  when only some details are stated. Do NOT use it for non-WordPress projects
  (React/Next/Laravel apps that merely use Parcel, Tailwind or Blade), for routine
  WordPress host moves or core/plugin version updates, or for scaffolding a
  brand-new theme rather than migrating an existing Bedrock one.
---

# Migrate Bedrock WordPress → native (Parcel + plain CSS)

This encodes a migration done before, so the same job goes fast and skips the
traps. The typical source is a **Roots Bedrock** repo whose custom theme is a
**child of the commercial Salient theme** (WPBakery page builder) coupled to
Webreact's private stack: `webreactor/framework`, `webreactor/vc-elements`,
`webreactor/acf`, `webreactor/pocket-knife` (+ `illuminate/*` for Blade), built
with **Laravel Mix + Tailwind + Sass**, with Blade used in a tiny number of
WPBakery "element" views. The end state is a repo whose **only git-tracked
content is the theme**, with a Parcel build and plain CSS, buildable and
deployable with no private package repos.

The two reference files hold the reusable detail — read them at the matching step:
- `references/php-decoupling.md` — native replacements for the webreactor stack + Blade→PHP (with code).
- `references/css-conversion.md` — Parcel config, `@apply`→CSS mapping, enqueue, delete list.

## Step 0 — Confirm the shape with the user (these forks change everything)

Do not assume. Ask (AskUserQuestion) before executing, because each answer
reshapes the work:

1. **Premium plugins** (ACF Pro, Gravity Forms, Salient+WPBakery, Polylang Pro,
   Yoast Premium, WP Mail SMTP Pro, WP Migrate DB Pro, WP Rocket) — they are
   **not on wordpress.org**. Commit them to git? Upload manually per site? Keep a
   minimal Composer just for them? (Common answer: uploaded manually per site.)
2. **WordPress core** — tracked in git, or provisioned per site? (Common: not in git.)
3. **wp-config** — plain per-site hand-written file, or an env-driven loader?
   (Common: plain per-site.)
4. **How far to strip styling** — keep Sass minus Tailwind, or go fully plain CSS
   (drop Sass too)? Fully-plain means rewriting the whole style tree by hand.

"Only the theme in git" + "upload premium myself" + "plain per-site wp-config" +
"fully plain CSS" is the configuration this skill is tuned for.

## Step 1 — Recon (map the actual stack before touching anything)

Confirm it really is this shape and find the coupling. Run in parallel:

```bash
# Bedrock? theme path? content dir?
sed -n '1,60p' composer.json 2>/dev/null; cat config/application.php 2>/dev/null | grep -E 'CONTENT_DIR|ABSPATH|install-dir'
# Theme location + framework coupling
THEME=$(find web/app/themes wp-content/themes -maxdepth 1 -type d 2>/dev/null | grep -v -E 'themes$|salient')
cat "$THEME"/composer.json "$THEME"/package.json 2>/dev/null
# How much Blade / where
find "$THEME" -name '*.blade.php'
# Build + style tree
ls "$THEME"/webpack.mix.js "$THEME"/webreact.config.js "$THEME"/tailwind.config.js "$THEME"/babel.config.js 2>/dev/null
find "$THEME/resources/styles" -name '*.scss'
# Every real coupling site (this is the whole job on the PHP side)
grep -rniE 'webreactor|illuminate|@apply|@tailwind' "$THEME" --include='*.php' --include='*.scss' --include='*.js'
```

The Blade footprint is usually tiny (often one view per WPBakery element). The
real work is decoupling from `webreactor/framework` and rewriting the styles.

## Step 2 — Execute in this order (lowest-risk, most-reversible first)

Work on a branch (`git checkout -b migrate/bedrock-to-native`) — never on the
default branch. Do NOT commit unless asked; leave it staged for review.

**2a. Build → Parcel + plain CSS.** Follow `references/css-conversion.md`:
rewrite `package.json` (Parcel `targets`, devDeps `parcel`/`rimraf`/`prettier`
only), rewrite `includes/enqueue.php` to single stable filenames, delete
`tailwind.config.js`/`webpack.mix.js`/`webreact.config.js`/`babel.config.js`,
convert every SCSS partial to plain CSS. Then `npm install && npm run prod` and
inspect `dist/styles/app.css` before going further — a fast, visible win that
de-risks the Parcel setup early.

**2b. Decouple PHP from webreactor.** Follow `references/php-decoupling.md`:
`spl_autoload_register` in `functions.php`; rewrite `includes/Admin/setup.php`
as a native hook-wiring kernel; strip the framework base off the CPT/taxonomy
"Action" classes; reimplement the WPBakery element with `vc_map` +
`add_shortcode`; delete `config/{providers,hooks,vc-elements}.php` and the
theme's `composer.json`/`composer.lock`. `php -l` everything.

**2c. Blade → native PHP.** Convert each `.blade.php`: `{!! $x !!}` → `<?= $x ?>`,
`{{ $x }}` → `<?= esc_html($x) ?>`, `@foreach(...)`/`@endforeach` →
`<?php foreach (...) : ?>`/`<?php endforeach; ?>`, `@if`/`@endif` likewise. The
element's render callback `ob_start()` + `include`s the partial and returns the
buffer (a shortcode must **return**, not echo). Keep output identical unless the
user opts into `esc_*` hardening.

**2d. Repo restructure → theme-only git.** See the gotchas below — this is where
time gets lost. Relocate the theme to `wp-content/themes/<name>`, write the
allowlist `.gitignore`, re-baseline the index, and delete Bedrock scaffolding
(`composer.json`, `composer.lock`, `auth.json`, `config/`, `wp-cli.yml`, root
`makefile`, `.deployignore`, `CHANGELOG.md`, `.env.example`). Point `.lando.yml`
`webroot` at `.`.

**2e. wp-config reference + security.** Capture the still-relevant constants from
`config/application.php` into a committed `docs/wp-config.sample.php` in the
theme (see `references/php-decoupling.md` for the constant list and two latent
bugs to fix). Delete `auth.json` and **flag credential rotation** (see gotcha 8).

## Gotchas (these cost the most time — internalise them)

1. **Parcel target named `scripts` collides with the npm `scripts` field.** Parcel
   reads a top-level field matching each target name as its output path, so a
   target called `scripts` makes the build fail with `Invalid distPath ... Expected
   type string`. Name the targets `css` and `js` (or anything not matching a
   package.json field). Target names don't affect output filenames — those come
   from the source basename — so `app.css`/`app.js` stay put.

2. **Do NOT create a `.postcssrc`.** Its *absence* is what keeps Parcel on its
   built-in lightningcss transformer, which handles minification, vendor-prefixing
   (per `browserslist`) and native-CSS-nesting downleveling with zero config and
   no Tailwind/Sass/PostCSS/autoprefixer dependencies.

3. **`git mv` fails after you delete files with plain `rm`.** During 2a/2b you
   delete SCSS/build-config/Blade files from disk; those stay in git's index as
   tracked-but-missing, and `git mv <themedir> <newpath>` then aborts with
   `fatal: bad source`. Don't fight it — relocate with a plain `mv` and re-baseline
   the index (gotcha 4). Renames are still detected at commit time by content.

4. **`.gitignore` does not untrack already-tracked files.** To make the repo
   theme-only you must re-baseline the index. After the physical `mv` and writing
   the allowlist `.gitignore`:
   ```bash
   git rm -r --cached --quiet . >/dev/null    # untrack everything, keep the working tree
   git add .                                  # re-add only what the new .gitignore allows
   ```
   Verify: `git ls-files | grep -v '^wp-content/themes/<name>/'` should show only
   `.gitignore` (+ `.lando.yml` if you chose to keep it), and
   `git ls-files | grep -cE 'node_modules|/dist/|parcel-cache'` should be 0.
   The allowlist `.gitignore` pattern:
   ```gitignore
   /*
   !/.gitignore
   !/.lando.yml
   !/wp-content/
   /wp-content/*
   !/wp-content/themes/
   /wp-content/themes/*
   !/wp-content/themes/<name>/
   /wp-content/themes/<name>/dist/
   /wp-content/themes/<name>/node_modules/
   /wp-content/themes/<name>/.parcel-cache/
   /wp-content/themes/<name>/vendor/
   ```

5. **Never `rm` the user's local environment/data.** `web/wp` (core),
   `web/app/uploads` (media), `web/app/plugins`, the Salient parent theme are
   gitignored working files you did not create. Untracking them via the re-baseline
   is fine; deleting them from disk is destructive and out of scope. Leave the
   legacy `web/` on disk and tell the user they can remove it after re-provisioning
   locally. Only physically delete Bedrock *project scaffolding* (composer/config/
   auth.json) — recoverable from history.

6. **An empty `resources/scripts/app.js` builds to a 0 B `dist/scripts/app.js`.**
   That's fine; `enqueue.php` guards with `file_exists`. Leave a one-line comment
   in the entry so it's obviously intentional.

7. **lightningcss rewrites `@media (max-width: 689px)` to range syntax
   `(width <= 689px)`** for the resolved `browserslist`. Valid for modern
   `defaults`; if the site must support Opera Mini / very old Safari, widen the
   browserslist (it will then emit classic `max-width`). Mention it; don't silently
   narrow support.

8. **Secrets outlive the file.** A committed `auth.json` (SatisPress/DeliciousBrains/
   Yoast tokens) and real infra values in `.env.example` remain in **git history**
   after you delete the files. Deleting is necessary but not sufficient — tell the
   user to rotate those credentials.

## Verify before handing back

```bash
# no old-stack references anywhere in the theme (comments included — reword any that hit)
grep -rniE 'webreactor|illuminate|@webreact|laravel-mix|tailwind|@apply|@tailwind|\.scss|\.blade' \
  --include='*.php' --include='*.js' --include='*.css' --include='*.json' \
  --exclude-dir=node_modules --exclude-dir=dist --exclude-dir=.parcel-cache "$THEME"
find "$THEME" -path '*/node_modules' -prune -o \( -name '*.scss' -o -name '*.blade.php' \) -print
for f in $(find "$THEME" -name '*.php' -not -path '*/node_modules/*'); do php -l "$f" >/dev/null || echo "SYNTAX: $f"; done
( cd "$THEME" && npm run prod )   # → dist/styles/app.css + dist/scripts/app.js
```

**Runtime check is the user's step** and needs a provisioned native WP (core +
Salient + premium plugins + DB) — you usually can't stand that up. Hand them the
checklist: WPBakery element renders identically, CPT/taxonomy + ACF options page
present, ACF JSON still loads, `app.css` enqueues with a `?ver=` hash, no PHP
fatals with `WP_DEBUG` on.

## Out of scope (state this explicitly, don't attempt silently)

Per-site provisioning (WP core, plugin installs, Salient upload, each site's
`wp-config.php`, Salient theme-option differences), the live-site `siteurl`/`home`
`/wp` → root cutover, and credential rotation. The repo deliverable is the theme;
per-site setup is the user's, done outside the repo.
