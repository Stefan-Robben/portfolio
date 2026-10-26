/*
 * Project: Niped (Radicle / WordPress)
 * Origineel pad: resources/js/components/card-stats.ts
 * Wat het doet: GSAP count-up van kerncijfers zodra ze in beeld komen; behoudt NL-notatie (23.879, 8,4, 75%) en respecteert prefers-reduced-motion.
 */

import { gsap } from 'gsap'
import { prefersReducedMotion } from '@scripts/lib/helpers'

// Stat values that count up on scroll: the stat cards and the key figures
// row in section-statistics.
const SELECTOR = '.card-stats__value, .section-statistics__value'
// Start once a value is a little way into the viewport, not right at its edge
const ROOT_MARGIN = '0px 0px -10% 0px'
const STAGGER = 0.15
const DURATION = 1.4
const EASE = 'power2.out'

// The observer watching each value, so a value can be released on its own.
const observers = new WeakMap<Element, IntersectionObserver>()

interface ParsedValue {
  prefix: string
  suffix: string
  target: number
  decimals: number
  useGrouping: boolean
}

/**
 * Split a formatted stat like "+23.879", "75%" or "8,4" into the parts
 * needed to animate it: the leading/trailing text to keep as-is, the target
 * number to count up to, and enough of the original formatting (decimal
 * places, thousands grouping) to reproduce it while counting.
 */
const parse = (text: string): ParsedValue | null => {
  const match = text.match(/[\d.,]+/)

  if (!match || match.index === undefined) return null

  const core = match[0]
  const decimals = core.includes(',') ? core.split(',').pop()?.length ?? 0 : 0
  const target = parseFloat(core.replace(/\./g, '').replace(',', '.'))

  if (Number.isNaN(target)) return null

  return {
    prefix: text.slice(0, match.index),
    suffix: text.slice(match.index + core.length),
    target,
    decimals,
    useGrouping: core.includes('.'),
  }
}

const format = (value: number, parsed: ParsedValue): string =>
  `${parsed.prefix}${new Intl.NumberFormat('nl-NL', {
    minimumFractionDigits: parsed.decimals,
    maximumFractionDigits: parsed.decimals,
    useGrouping: parsed.useGrouping,
  }).format(value)}${parsed.suffix}`

const animate = (el: HTMLElement, index: number): void => {
  const parsed = parse(el.textContent ?? '')

  if (!parsed) return

  if (prefersReducedMotion()) {
    el.textContent = format(parsed.target, parsed)
    return
  }

  const proxy = { value: 0 }

  el.textContent = format(0, parsed)

  gsap.to(proxy, {
    value: parsed.target,
    duration: DURATION,
    delay: index * STAGGER,
    ease: EASE,
    onUpdate: () => {
      el.textContent = format(proxy.value, parsed)
    },
  })
}

/**
 * Count stat values up from zero as their cards scroll into view. Values that
 * enter the viewport together are counted with a stagger between them.
 *
 * An IntersectionObserver rather than ScrollTrigger: ScrollTrigger measures
 * trigger positions up front, so lazy-loaded images above a stat pushed it
 * down after the fact and the count started only once it had scrolled past.
 *
 * Pass `root` to only count values inside it, e.g. markup FacetWP just put in
 * the results, without restarting the counts elsewhere on the page.
 */
export const cardStatsCounters = (root: ParentNode = document): void => {
  // Skip values a previous call already watches, so calling this again for
  // new markup never restarts a count.
  const values = [...root.querySelectorAll<HTMLElement>(SELECTOR)]
    .filter((el) => !el.dataset.countObserved)

  if (!values.length) return

  const observer = new IntersectionObserver(
    (entries) => {
      entries
        .filter((entry) => entry.isIntersecting)
        .forEach((entry, index) => {
          observer.unobserve(entry.target)
          observers.delete(entry.target)
          animate(entry.target as HTMLElement, index)
        })
    },
    { rootMargin: ROOT_MARGIN },
  )

  values.forEach((el) => {
    el.dataset.countObserved = 'true'
    observers.set(el, observer)
    observer.observe(el)
  })
}

/**
 * Stop watching the values inside `root` that haven't counted yet, e.g.
 * results FacetWP is about to replace, so the observers don't keep them alive.
 */
export const stopCardStatsCounters = (root: ParentNode): void => {
  root.querySelectorAll<HTMLElement>(SELECTOR).forEach((el) => {
    observers.get(el)?.unobserve(el)
    observers.delete(el)
  })
}
