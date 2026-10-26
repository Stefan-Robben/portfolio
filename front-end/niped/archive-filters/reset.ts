/*
 * Project: Niped (Radicle / WordPress)
 * Origineel pad: resources/js/vendors/facetwp/reset.ts
 * Wat het doet: Progressive enhancement: 'Filters wissen'-link werkt zonder JS en reset met JS alle facets zonder page reload.
 */

/**
 * "Filters wissen" links (`[data-facetwp-reset]`) point at the unfiltered
 * archive, so they work without JavaScript. With FacetWP loaded they reset
 * every facet in place instead of reloading the page. Delegated, because
 * FacetWP replaces the results (and the link with them) on every refresh.
 */
export const facetWpReset = (): void => {
  document.addEventListener('click', (event) => {
    const link = (event.target as Element | null)?.closest('[data-facetwp-reset]')

    // A modified or non-primary click (new tab, new window, download) keeps
    // the link's own behaviour: opening the unfiltered archive.
    const isPlainClick = event.button === 0
      && !event.metaKey && !event.ctrlKey && !event.shiftKey && !event.altKey

    if (!link || !window.FWP || !isPlainClick) {
      return
    }

    event.preventDefault()
    window.FWP.reset()
  })
}
