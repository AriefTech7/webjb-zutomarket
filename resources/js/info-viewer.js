// "Info Terbaru" on the home page: a poster opens in a dialog with the other posters as thumbnails and its caption.
// Without JavaScript (or <dialog> support) the poster link still opens the image or its own link.
export function initialiseInfoViewer() {
    const dialog = document.querySelector('[data-info-viewer]');
    if (!dialog || typeof dialog.showModal !== 'function') return;
    const thumbs = [...dialog.querySelectorAll('[data-info-goto]')];
    const count = new Set([...dialog.querySelectorAll('[data-info-slide]')].map((slide) => slide.dataset.infoSlide)).size;
    let current = 0;

    const show = (index) => {
        current = (index + count) % count;
        dialog.querySelectorAll('[data-info-slide]').forEach((slide) => {
            slide.hidden = Number(slide.dataset.infoSlide) !== current;
        });
        thumbs.forEach((thumb) => {
            const active = Number(thumb.dataset.infoGoto) === current;
            thumb.setAttribute('aria-current', String(active));
            if (active) thumb.scrollIntoView({ block: 'nearest', inline: 'nearest' });
        });
    };

    document.querySelectorAll('[data-info-open]').forEach((poster) => {
        poster.addEventListener('click', (event) => {
            event.preventDefault();
            show(Number(poster.dataset.infoOpen));
            dialog.showModal();
        });
    });
    dialog.addEventListener('click', (event) => {
        const thumb = event.target.closest('[data-info-goto]');
        // A click on the dimmed backdrop lands on the <dialog> itself.
        if (event.target === dialog || event.target.closest('[data-info-close]')) dialog.close();
        else if (thumb) show(Number(thumb.dataset.infoGoto));
    });
    dialog.addEventListener('keydown', (event) => {
        if (count < 2) return;
        if (event.key === 'ArrowRight') show(current + 1);
        if (event.key === 'ArrowLeft') show(current - 1);
    });

    // A shared link like "/#info-2" opens that poster (numbered from 1, in the order shown).
    const linked = /^#info-(\d+)$/.exec(window.location.hash);
    if (linked && Number(linked[1]) >= 1 && Number(linked[1]) <= count) {
        show(Number(linked[1]) - 1);
        dialog.showModal();
    }
}
