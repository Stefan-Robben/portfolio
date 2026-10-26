/*
 * Project: Niped (Radicle / WordPress)
 * Origineel pad: resources/js/vendors/facetwp/loading.ts
 * Wat het doet: Toont ghost cards en zet aria-busy terwijl FacetWP nieuwe resultaten laadt.
 */

import { facetWpLoad, facetWpRefresh } from '@scripts/lib/lifecycle'

export const facetWpLoading = (): void => {
  const ghosts = document.querySelector<HTMLTemplateElement>('template[data-facetwp-ghosts]')

  const setLoading = (loading: boolean): void => {
    document.querySelectorAll<HTMLElement>('.facetwp-template').forEach((template) => {
      if (loading && ghosts) {
        template.replaceChildren(ghosts.content.cloneNode(true))
      }

      template.classList.toggle('is-loading', loading)
      template.setAttribute('aria-busy', String(loading))
    })
  }

  facetWpRefresh(() => {
    if (window.FWP?.loaded) {
      setLoading(true)
    }
  })

  facetWpLoad(() => setLoading(false))
}
