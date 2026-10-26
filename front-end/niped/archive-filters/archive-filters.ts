/*
 * Project: Niped (Radicle / WordPress)
 * Origineel pad: resources/js/components/alpine/archive-filters.ts
 * Wat het doet: Alpine.js data-component: open/dicht en het aantal gekozen filters ('Filteren (n)') synchroon met FacetWP.
 */

import { facetWpLoad } from '@scripts/lib/lifecycle'

interface ArchiveFilters {
  open: boolean
  count: number
  update (): void
}

/**
 * Toggles the filter panel and keeps the "Filteren (n)" count of options
 * selected across the given facets in sync with FacetWP, which fires
 * `facetwp-loaded` after its first render and after every refresh.
 */
export default (facets: string[] = []) => ({
  open: false,
  count: 0,

  init (this: ArchiveFilters): void {
    facetWpLoad(() => this.update())
    this.update()
  },

  update (this: ArchiveFilters): void {
    // FWP.facets is only filled in once FacetWP has run its first refresh.
    const selected = window.FWP?.facets

    if (!selected) {
      return
    }

    this.count = facets.reduce(
      (total, facet) => total + (selected[facet]?.length ?? 0),
      0,
    )
  },
})
