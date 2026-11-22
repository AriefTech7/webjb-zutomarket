const pad = (value) => String(value).padStart(2, '0');

/** Formats the time left like the server does, e.g. "05:04:03" or "2 hari 05:04:03". */
export function formatRemaining(milliseconds) {
    const seconds = Math.floor(milliseconds / 1000);
    if (!(seconds > 0)) return 'Berakhir';
    const clock = `${pad(Math.floor((seconds % 86400) / 3600))}:${pad(Math.floor((seconds % 3600) / 60))}:${pad(seconds % 60)}`;

    return seconds >= 86400 ? `${Math.floor(seconds / 86400)} hari ${clock}` : clock;
}

/** { d, h, m, s } as two-digit strings (days not padded), all "00" once the time is up. */
export function remainingParts(milliseconds) {
    const seconds = Math.max(0, Math.floor(milliseconds / 1000));

    return { d: String(Math.floor(seconds / 86400)), h: pad(Math.floor((seconds % 86400) / 3600)), m: pad(Math.floor((seconds % 3600) / 60)), s: pad(seconds % 60) };
}

export function initialiseCountdowns() {
    const counters = [...document.querySelectorAll('[data-countdown]')];
    // Boxed timers (account page flash sale): <… data-countdown-parts="ISO"> with [data-cd="d|h|m|s"] inside.
    const boxed = [...document.querySelectorAll('[data-countdown-parts]')];
    if (!counters.length && !boxed.length) return;
    const tick = () => {
        const now = Date.now();
        counters.forEach((counter) => {
            counter.textContent = formatRemaining(Date.parse(counter.dataset.countdown) - now);
        });
        boxed.forEach((timer) => {
            const parts = remainingParts(Date.parse(timer.dataset.countdownParts) - now);
            timer.querySelectorAll('[data-cd]').forEach((cell) => { cell.textContent = parts[cell.dataset.cd]; });
            timer.querySelectorAll('[data-cd-days]').forEach((box) => { box.hidden = parts.d === '0'; });
        });
    };
    tick();
    setInterval(tick, 1000);
}
