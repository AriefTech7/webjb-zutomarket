const pad = (value) => String(value).padStart(2, '0');

/** Formats the time left like the server does, e.g. "05:04:03" or "2 hari 05:04:03". */
export function formatRemaining(milliseconds) {
    const seconds = Math.floor(milliseconds / 1000);
    if (!(seconds > 0)) return 'Berakhir';
    const clock = `${pad(Math.floor((seconds % 86400) / 3600))}:${pad(Math.floor((seconds % 3600) / 60))}:${pad(seconds % 60)}`;

    return seconds >= 86400 ? `${Math.floor(seconds / 86400)} hari ${clock}` : clock;
}

export function initialiseCountdowns() {
    const counters = [...document.querySelectorAll('[data-countdown]')];
    if (!counters.length) return;
    const tick = () => {
        const now = Date.now();
        counters.forEach((counter) => {
            counter.textContent = formatRemaining(Date.parse(counter.dataset.countdown) - now);
        });
    };
    tick();
    setInterval(tick, 1000);
}
