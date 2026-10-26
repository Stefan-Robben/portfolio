/*
 * Project: Niped (Radicle / WordPress)
 * Origineel pad: resources/js/components/chart.ts
 * Wat het doet: Laadt Chart.js pas (dynamic import) als een grafiek bijna in beeld is en bouwt de config met kleuren uit de theme tokens.
 */

import type { Chart as ChartInstance, ChartConfiguration, ChartType } from 'chart.js'
import { prefersReducedMotion } from '@scripts/lib/helpers'

// Chart blocks render a data table and an empty canvas. Chart.js is fetched
// the first time a chart comes near the viewport, so pages without one (or
// visitors who never scroll that far) don't pay for it.

const SELECTOR = '[data-chart]'
const ROOT_MARGIN = '0px 0px 200px 0px'

// Series colours in order, from the theme: the same accent/primary alternation
// as the benchmark block, then the remaining brand colours.
const SERIES_TOKENS = [
  '--color-accent',
  '--color-primary',
  '--color-tertiary',
  '--color-primary-600',
  '--color-secondary',
  '--color-neutral-500',
]

interface ChartData {
  type: 'bar' | 'horizontal-bar' | 'line' | 'pie' | 'doughnut'
  labels: string[]
  datasets: { label: string, values: (number | null)[] }[]
  unit: string
}

const instances = new Map<HTMLElement, ChartInstance>()
const observers = new WeakMap<HTMLElement, IntersectionObserver>()

const token = (name: string): string =>
  getComputedStyle(document.documentElement).getPropertyValue(name).trim()

const numberFormat = new Intl.NumberFormat('nl-NL', { maximumFractionDigits: 1 })

const format = (value: number, unit: string): string => {
  const number = numberFormat.format(value)

  if (!unit) return number
  if (unit === '%') return `${number}%`
  if (unit === '€') return `€ ${number}`

  return `${number} ${unit}`
}

// Chart.js draws an array label as one line per entry.
const wrap = (label: string, max = 14): string | string[] => {
  if (label.length <= max) return label

  return label.split(' ').reduce<string[]>((lines, word) => {
    const last = lines[lines.length - 1]

    if (last !== undefined && `${last} ${word}`.length <= max) {
      lines[lines.length - 1] = `${last} ${word}`
    } else {
      lines.push(word)
    }

    return lines
  }, [])
}

const buildConfig = (data: ChartData): ChartConfiguration => {
  const circular = data.type === 'pie' || data.type === 'doughnut'
  const colours = SERIES_TOKENS.map(token)
  const colourAt = (index: number): string => colours[index % colours.length]
  const grid = token('--color-neutral-300')
  const multiple = data.datasets.length > 1

  const datasets = data.datasets.map((dataset, index) => ({
    label: dataset.label,
    data: dataset.values,
    // A circle colours its slices, the other types their series.
    backgroundColor: circular ? data.labels.map((_, slice) => colourAt(slice)) : colourAt(index),
    borderColor: circular ? token('--color-neutral-white') : colourAt(index),
    borderWidth: circular ? 2 : data.type === 'line' ? 3 : 0,
    // --radius-input (8px), as on the benchmark bars
    borderRadius: circular ? 0 : 8,
    borderSkipped: false,
    pointRadius: 4,
    pointHoverRadius: 6,
    tension: 0.3,
    // A missing value stays a visible gap rather than an invented line.
    spanGaps: false,
  }))

  const valueAxis = {
    // Bars are read by their length, so they need a zero baseline; a line is
    // read by its shape, which a forced zero would flatten.
    beginAtZero: data.type !== 'line',
    grid: { color: grid },
    border: { display: false },
    ticks: { callback: (value: string | number) => format(Number(value), data.unit) },
  }
  const categoryAxis = {
    grid: { display: false },
    border: { color: grid },
    ticks: {
      // Long labels wrap instead of being cut off at the canvas edge.
      callback (this: { getLabelForValue: (value: number) => string }, value: string | number) {
        return wrap(this.getLabelForValue(Number(value)))
      },
    },
  }
  const horizontal = data.type === 'horizontal-bar'

  return {
    type: (horizontal ? 'bar' : data.type) as ChartType,
    data: { labels: data.labels, datasets },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      animation: prefersReducedMotion() ? false : undefined,
      indexAxis: horizontal ? 'y' : 'x',
      cutout: data.type === 'doughnut' ? '60%' : undefined,
      scales: circular ? {} : {
        x: horizontal ? valueAxis : categoryAxis,
        y: horizontal ? categoryAxis : valueAxis,
      },
      plugins: {
        legend: {
          display: multiple || circular,
          position: 'bottom',
          align: 'start',
          labels: { usePointStyle: true, pointStyle: 'circle', boxWidth: 8, boxHeight: 8, padding: 16 },
        },
        tooltip: {
          backgroundColor: token('--color-neutral-900'),
          padding: 12,
          cornerRadius: 8,
          usePointStyle: true,
          callbacks: {
            label: (context) => {
              const name = circular ? context.label : context.dataset.label
              const value = format(Number(context.raw), data.unit)

              return name ? `${name}: ${value}` : value
            },
          },
        },
      },
    } as ChartConfiguration['options'],
  } as ChartConfiguration
}

const loadChartJs = async () => {
  const { Chart, BarController, LineController, PieController, DoughnutController, CategoryScale, LinearScale, BarElement, LineElement, PointElement, ArcElement, Tooltip, Legend } = await import('chart.js')

  Chart.register(BarController, LineController, PieController, DoughnutController, CategoryScale, LinearScale, BarElement, LineElement, PointElement, ArcElement, Tooltip, Legend)

  Chart.defaults.font.family = token('--font-body')
  Chart.defaults.font.size = 14
  Chart.defaults.color = token('--color-body')

  return Chart
}

let chartJs: ReturnType<typeof loadChartJs> | null = null

const draw = async (container: HTMLElement): Promise<void> => {
  const canvas = container.querySelector('canvas')

  if (!canvas || instances.has(container)) {
    return
  }

  let data: ChartData

  try {
    data = JSON.parse(container.dataset.chart ?? '') as ChartData
  } catch {
    return
  }

  chartJs ??= loadChartJs()
  const Chart = await chartJs

  instances.set(container, new Chart(canvas, buildConfig(data)))
}

export const charts = (scope: ParentNode = document): void => {
  scope.querySelectorAll<HTMLElement>(SELECTOR).forEach((container) => {
    if (observers.has(container) || instances.has(container)) {
      return
    }

    const observer = new IntersectionObserver((entries) => {
      if (entries.some((entry) => entry.isIntersecting)) {
        observer.disconnect()
        observers.delete(container)
        void draw(container)
      }
    }, { rootMargin: ROOT_MARGIN })

    observer.observe(container)
    observers.set(container, observer)
  })
}

export const destroyCharts = (scope: ParentNode = document): void => {
  scope.querySelectorAll<HTMLElement>(SELECTOR).forEach((container) => {
    observers.get(container)?.disconnect()
    observers.delete(container)
    instances.get(container)?.destroy()
    instances.delete(container)
  })
}
