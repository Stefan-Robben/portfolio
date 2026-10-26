/*
 * Project: Vonders (Radicle / WordPress)
 * Origineel pad: resources/scripts/vendors/gsap/tiltedLoop.ts
 * Wat het doet: Schuine, oneindige logo-carrousel die versnelt en van richting wisselt met de scrollrichting (GSAP Observer + ScrollTrigger).
 */

import {fillRowWithClones, horizontalLoop, setFixedRowWidth} from "../../utils/gsap/horizontalLoop.js";
import {gsap} from "gsap";
import {Observer} from "gsap/Observer";
import {ScrollTrigger} from "gsap/ScrollTrigger";
import {hasReducedMotion} from "../../utils/helpers.js";

gsap.registerPlugin(ScrollTrigger, Observer);

const animateRow = (row: HTMLElement, items: Array<HTMLElement>) => {
  return horizontalLoop(items, {
    repeat: -1,
    speed: 0.25,
    snap: 5,
    reversed: row.classList.contains("js-client-swiper-tilted--reversed"),
  });
};

const onScroll = (hasDirectionalChange: boolean, timelines: Map<HTMLElement, GSAPTimeline>) => {
  timelines.forEach(tl => {
    if (hasDirectionalChange) {
      tl.toggleReverse();
    }

    gsap.fromTo(tl, {
        timeScale: tl.reversed() ? -7 : 7,
        overwrite: true,
      }, {
        timeScale: tl.reversed() ? -1 : 1,
        duration: 0.6,
        ease: "power4.out",
        overwrite: true,
      }
    );
  });
}

export default () => {
  const target = document.querySelectorAll('.js-client-swiper-tilted')

  if (!target) return;

  const rows = gsap.utils.toArray<HTMLElement>(target);
  const timelines = new Map<HTMLElement, GSAPTimeline>();
  let prevDirection = 1;

  if (!rows.length) return;

  rows.forEach(row => {
    let items = gsap.utils.toArray<HTMLElement>('.clients__item-wrapper', row);

    if (!items.length) {
      return;
    }

    items = items.concat(fillRowWithClones(row, items));

    if (hasReducedMotion()) return;

    setFixedRowWidth(row, items);
    timelines.set(row, animateRow(row, items));

    timelines.forEach(tl => {
      tl.pause()
    });

    ScrollTrigger.create({
      trigger: row,
      start: "top bottom",
      end: "bottom top",
      onToggle: self => {
        timelines.forEach(tl => {
          self.isActive ? tl.resume() : tl.pause();
        });
      },
    });
  });

  Observer.create({
    type: "scroll",
    tolerance: 13,
    onChangeY: self => {
      const hasDirectionalChange = (self.deltaY > 0 && prevDirection < 0) || (self.deltaY < 0 && prevDirection > 0);
      if (hasDirectionalChange) prevDirection = self.deltaY;

      onScroll(hasDirectionalChange, timelines);
    },
  });
};
