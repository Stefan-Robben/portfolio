/*
 * Project: Vonders (Radicle / WordPress)
 * Origineel pad: resources/scripts/vendors/gsap/zoom.ts
 * Wat het doet: Herbruikbare zoom-in/zoom-out bij binnenkomst in beeld via utility classes .fx-zoom-in/.fx-zoom-out.
 */

import { gsap } from 'gsap'
import { ScrollTrigger } from 'gsap/ScrollTrigger'

gsap.registerPlugin(ScrollTrigger)

interface ZoomOptions {
  duration?: number
  ease?: string
  scrollTrigger?: ScrollTrigger.Vars
}

interface ZoomProps {
  selectors: string | string[]
  fromScale?: number
  toScale?: number
  options?: ZoomOptions
  container?: Document | HTMLElement
}

const zoom = ({ selectors, fromScale = 1.05, toScale = 1, options = {}, container = document }: ZoomProps): void => {
  const selectorArray: string[] = Array.isArray(selectors) ? selectors : [selectors]

  const elements: HTMLElement[] = selectorArray.flatMap((sel: string) =>
    Array.from(container.querySelectorAll<HTMLElement>(sel))
  )

  if (!elements.length) return

  elements.forEach((el: HTMLElement): void => {
    if (el.dataset.animated) return

    const anim: gsap.core.Tween = gsap.fromTo(
      el,
      { scale: fromScale },
      {
        scale: toScale,
        duration: options.duration ?? 1.8,
        ease: options.ease ?? 'power3.out',
        onComplete: (): void => {
          el.dataset.animated = 'true'
        }
      }
    )

    ScrollTrigger.create({
      trigger: el,
      animation: anim,
      toggleActions: 'play none none none',
      once: true,
      start: options.scrollTrigger?.start ?? 'top 90%',
      ...(options.scrollTrigger || {}),
    })
  })
}

export const zoomOutOnEnter = (props: ZoomProps): void => zoom({ fromScale: 1.05, toScale: 1, ...props })
export const zoomInOnEnter = (props: ZoomProps): void => zoom({ fromScale: 0.95, toScale: 1, ...props })

const zoomController = (container: Document | HTMLElement = document): void => {
  zoomOutOnEnter({ selectors: ['.fx-zoom-out'], container })
  zoomInOnEnter({ selectors: ['.fx-zoom-in'], container })
}

export const initZoomAnimations = (container: Document | HTMLElement = document): void => {
  zoomController(container)
}

export default initZoomAnimations

