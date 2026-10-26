/*
 * Project: Vonders (Radicle / WordPress)
 * Origineel pad: resources/scripts/vendors/gsap/parallax.ts
 * Wat het doet: Herbruikbare parallax-module: elementen met .fx-parallax-up/-down bewegen met scrub mee op scroll.
 */

import { gsap } from 'gsap'
import { ScrollTrigger } from 'gsap/ScrollTrigger'

gsap.registerPlugin(ScrollTrigger)

const baseDistance: number = 8

interface ParallaxOptions {
  start?: string
  end?: string
  scrub?: number | boolean
}

interface ParallaxProps {
  selectors: string | string[]
  yPercent?: number
  options?: ParallaxOptions
  container?: Document | HTMLElement
}

const parallax = ({ selectors, yPercent = 0, options = {}, container = document }: ParallaxProps): void => {
  const selectorArray: string[] = Array.isArray(selectors) ? selectors : [selectors]

  const elements: HTMLElement[] = selectorArray.flatMap((sel: string) =>
    Array.from(container.querySelectorAll<HTMLElement>(sel))
  )

  if (!elements.length) return

  elements.forEach((el: HTMLElement): void => {
    if (el.dataset.animated) return

    gsap.to(el, {
      yPercent: yPercent,
      ease: 'none',
      scrollTrigger: {
        trigger: el,
        start: options.start ?? 'top bottom',
        end: options.end ?? 'bottom top',
        scrub: options.scrub ?? 2.5,
      },
    })

    el.dataset.animated = 'true'
  })
}

export const parallaxUp = (props: ParallaxProps): void => parallax({ yPercent: -baseDistance, ...props })
export const parallaxDown = (props: ParallaxProps): void => parallax({ yPercent: baseDistance, ...props })

const parallaxController = (container: Document | HTMLElement = document): void => {
  parallaxUp({ selectors: ['.fx-parallax-up'], container })
  parallaxDown({ selectors: ['.fx-parallax-down'], container })
}

export const initParallaxAnimations = (container: Document | HTMLElement = document): void => {
  parallaxController(container)
}

export default initParallaxAnimations

