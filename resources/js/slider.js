// Home banner slider: swipe/scroll-snap track, dots, arrows and autoplay that pauses while people interact.

export const SLIDER_INTERVAL = 5000;

/** Index after moving `step` slides, wrapping around both ends. */
export function wrapIndex(index, step, count) {
    if (count <= 0) return 0;

    return (((index + step) % count) + count) % count;
}

export function initialiseSliders(root = document) {
    const reduceMotion = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches ?? false;

    root.querySelectorAll('[data-slider]').forEach((slider) => {
        const track = slider.querySelector('[data-slider-track]');
        const dots = [...slider.querySelectorAll('[data-slider-dot]')];
        const count = track.children.length;
        if (count < 2) return;

        const current = () => Math.round(track.scrollLeft / Math.max(track.clientWidth, 1));
        const goTo = (index) => track.scrollTo({ left: wrapIndex(index, 0, count) * track.clientWidth, behavior: reduceMotion ? 'auto' : 'smooth' });
        const markDot = () => {
            const active = current();
            dots.forEach((dot, i) => dot.setAttribute('aria-current', String(i === active)));
        };

        slider.querySelector('[data-slider-prev]')?.addEventListener('click', () => goTo(wrapIndex(current(), -1, count)));
        slider.querySelector('[data-slider-next]')?.addEventListener('click', () => goTo(wrapIndex(current(), 1, count)));
        dots.forEach((dot) => dot.addEventListener('click', () => goTo(Number(dot.dataset.sliderDot))));
        let frame;
        track.addEventListener('scroll', () => {
            cancelAnimationFrame(frame);
            frame = requestAnimationFrame(markDot);
        }, { passive: true });

        if (reduceMotion) return;
        let paused = false;
        const pause = () => { paused = true; };
        const resume = () => { paused = false; };
        slider.addEventListener('pointerenter', pause);
        slider.addEventListener('pointerleave', resume);
        slider.addEventListener('focusin', pause);
        slider.addEventListener('focusout', resume);
        track.addEventListener('touchstart', pause, { passive: true });
        track.addEventListener('touchend', () => setTimeout(resume, SLIDER_INTERVAL), { passive: true });
        setInterval(() => {
            if (!paused && document.visibilityState === 'visible') goTo(wrapIndex(current(), 1, count));
        }, SLIDER_INTERVAL);
    });
}
