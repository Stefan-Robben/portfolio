/*
 * Project: Niped (Radicle / WordPress)
 * Origineel pad: .claude/skills/blade-component/scripts/render-check.mjs
 * Wat het doet: Playwright-script dat componenten rendert op meerdere viewports, computed styles meet en screenshots maakt om te vergelijken met de Figma-frames.
 */

/**
 * Measure and screenshot rendered components, so a design can be checked
 * against real computed pixels instead of a guess.
 *
 * Reading the compiled CSS proves a utility resolved to *something*; this proves
 * the element ends up the size and colour the design asks for, at every
 * breakpoint. It is also the only practical way to see the component, because
 * Blade cannot be compiled standalone (the component tag compiler needs a booted
 * app) and the in-app browser pane blocks the site's own CSS and JS as
 * ERR_BLOCKED_BY_CLIENT.
 *
 * Usage:
 *   node .claude/skills/blade-component/scripts/render-check.mjs <url> <spec.json> <outdir>
 *
 * Run it from the project root so Node can resolve @playwright/test.
 *
 * spec.json:
 * {
 *   "viewports": { "desktop": [1280, 1400], "mobile": [390, 1200] },
 *   "targets": [
 *     {
 *       "name": "base",
 *       "selector": ".card-testimonial--type-base",
 *       "measure": ["padding", "rowGap", "backgroundColor", "borderRadius"],
 *       "children": {
 *         "stars": { "selector": ".card-testimonial__stars", "measure": ["columnGap"] }
 *       }
 *     }
 *   ]
 * }
 *
 * Every measured element also reports its rendered width/height, which is
 * usually what you want to compare against the Figma frame.
 *
 * Prints one JSON blob of measurements and writes <outdir>/<viewport>-<name>.png.
 */
import { chromium } from '@playwright/test';
import { mkdirSync, readFileSync } from 'node:fs';

const [url, specPath, outDir = '.'] = process.argv.slice(2);
if (!url || !specPath) {
  console.error('usage: render-check.mjs <url> <spec.json> <outdir>');
  process.exit(1);
}

const spec = JSON.parse(readFileSync(specPath, 'utf8'));
const viewports = spec.viewports ?? { desktop: [1280, 1400] };
mkdirSync(outDir, { recursive: true });

// Chrome rather than Playwright's bundled Chromium: `npx playwright install`
// has not been run in this repo, but the system Chrome is present.
const browser = await chromium.launch({ channel: 'chrome' });
// Lando serves the site over HTTPS with a self-signed certificate.
const page = await browser.newPage({ ignoreHTTPSErrors: true, deviceScaleFactor: 2 });

const consoleErrors = [];
const failedRequests = [];
page.on('console', m => m.type() === 'error' && consoleErrors.push(m.text()));
page.on('requestfailed', r => failedRequests.push(`${r.url()} ${r.failure()?.errorText ?? ''}`));

await page.goto(url, { waitUntil: 'load' });

const results = {};

for (const [vpName, [width, height]] of Object.entries(viewports)) {
  await page.setViewportSize({ width, height });
  // Let the responsive layout and any lazy images settle.
  await page.waitForTimeout(900);

  results[vpName] = await page.evaluate(spec => {
    const read = (el, keys = []) => {
      if (!el) return 'MISSING';
      const cs = getComputedStyle(el);
      const r = el.getBoundingClientRect();
      const out = { w: +r.width.toFixed(2), h: +r.height.toFixed(2) };
      for (const k of keys) out[k] = cs[k];
      return out;
    };

    const out = {};
    for (const t of spec.targets) {
      const root = document.querySelector(t.selector);
      out[t.name] = read(root, t.measure);
      for (const [childName, child] of Object.entries(t.children ?? {})) {
        out[`${t.name}.${childName}`] = read(
          root ? root.querySelector(child.selector) : null,
          child.measure,
        );
      }
    }
    return out;
  }, spec);

  for (const t of spec.targets) {
    const el = page.locator(t.selector).first();
    if (!(await el.count())) continue;
    await el.scrollIntoViewIfNeeded();
    await page.waitForTimeout(250);
    await el.screenshot({ path: `${outDir}/${vpName}-${t.name}.png` });
  }
}

await browser.close();

console.log(JSON.stringify({
  url,
  measurements: results,
  consoleErrors,
  // Remote test images (picsum.photos and friends) may legitimately fail in a
  // sandboxed network; a failing /build/assets/*.css is what actually matters.
  failedRequests,
}, null, 1));
