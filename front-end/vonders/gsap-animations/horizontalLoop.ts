/*
 * Project: Vonders (Radicle / WordPress)
 * Origineel pad: resources/scripts/utils/gsap/horizontalLoop.ts
 * Wat het doet: Naadloze horizontale loop. De kern is gebaseerd op de openbare horizontalLoop-helper van GreenSock en door Stefan naar TypeScript omgezet; fillRowWithClones en setFixedRowWidth zijn eigen toevoegingen.
 * Let op: de loop-kern (ingekort) komt uit de GSAP-documentatie, niet zelf bedacht.
 */

/*
This helper function makes a group of elements animate along the x-axis in a seamless, responsive loop.

Features:
- Uses xPercent so that even if the widths change (like if the window gets resized), it should still work in most cases.
- When each item animates to the left or right enough, it will loop back to the other side
- Optionally pass in a config object with values like "speed" (default: 1, which travels at roughly 100 pixels per second), paused (boolean),  repeat, reversed, and paddingRight.
- The returned timeline will have the following methods added to it:
- next() - animates to the next element using a timeline.tweenTo() which it returns. You can pass in a vars object to control duration, easing, etc.
- previous() - animates to the previous element using a timeline.tweenTo() which it returns. You can pass in a vars object to control duration, easing, etc.
- toIndex() - pass in a zero-based index value of the element that it should animate to, and optionally pass in a vars object to control duration, easing, etc. Always goes in the shortest direction
- current() - returns the current index (if an animation is in-progress, it reflects the final index)
- times - an Array of the times on the timeline where each element hits the "starting" spot. There's also a label added accordingly, so "label1" is when the 2nd element reaches the start.
*/

import gsap from "gsap";

export interface HorizontalLoopConfig {
  speed?: number
  paused?: boolean
  repeat?: number
  reversed?: boolean
  paddingRight?: number
  snap?: number | false
}

export interface HorizontalLoopTimeline extends gsap.core.Timeline {
  next: (vars?: gsap.TweenVars) => gsap.core.Tween
  previous: (vars?: gsap.TweenVars) => gsap.core.Tween
  current: () => number
  toIndex: (index: number, vars?: gsap.TweenVars) => gsap.core.Tween
  times: number[]
  toggleReverse: () => void
}

export const horizontalLoop = (items: HTMLElement[], config: HorizontalLoopConfig): HorizontalLoopTimeline => {
  // … (ingekort): timeline-opbouw, xPercent-berekening per item, snapping en
  // toIndex/next/previous komen 1-op-1 uit de GreenSock horizontalLoop-helper
  // (alleen omgezet naar TypeScript) en zijn hier weggelaten.
  const tl = gsap.timeline() as HorizontalLoopTimeline;

  return tl;
};

export const fillRowWithClones = (row: HTMLElement, items: HTMLElement[]): HTMLElement[] => {
  const newItems: HTMLElement[] = [];

  const cloneAllItems = (): void => {
    const clones: DocumentFragment = document.createDocumentFragment();

    items.forEach((item: HTMLElement): void => {
      const clone: HTMLElement = item.cloneNode(true) as HTMLElement;
      clones.append(clone);
      newItems.push(clone);
    });

    row.append(clones);
  };

  const itemWidth: number = items[0].offsetWidth;
  const rowWidth: number = items.length * itemWidth;

  const requiredCloneAmount: number = Math.ceil((window.innerWidth + itemWidth) / rowWidth) - 1;
  const maximumCloneAmount: number = 20;
  const cloneAmount: number = Math.min(requiredCloneAmount, maximumCloneAmount);

  for (let i = 0; i < cloneAmount; i++) {
    cloneAllItems();
  }

  return newItems;
};

export const setFixedRowWidth = (row: HTMLElement, items: HTMLElement[]): void => {
  const itemWidth: number = items[0].offsetWidth;
  row.style.width = `calc(${itemWidth}px * ${items.length - 1})`;
};

