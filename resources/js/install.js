export const INSTALL_DISMISS_KEY = 'zutomarket-install-dismissed';
export const INSTALL_SNOOZE_MS = 7 * 24 * 60 * 60 * 1000;
const SHOW_DELAY_MS = 3000;

/** Offer the install prompt unless the app already runs installed or was dismissed in the last 7 days. */
export function shouldOfferInstall(storage, now, isStandalone) {
    if (isStandalone) return false;
    try {
        const dismissedAt = Number(storage.getItem(INSTALL_DISMISS_KEY));
        return !(dismissedAt > 0 && now - dismissedAt < INSTALL_SNOOZE_MS);
    } catch {
        return true;
    }
}

export function isIosSafari(userAgent) {
    return /iphone|ipad|ipod/i.test(userAgent) && /safari/i.test(userAgent) && !/crios|fxios|edgios/i.test(userAgent);
}

export function initialiseInstallPrompt() {
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js').catch(() => {}));
    }
    const box = document.querySelector('[data-install-prompt]');
    if (!box) return;
    let storage = null;
    try {
        storage = window.localStorage;
    } catch {
        storage = null;
    }
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
    if (!shouldOfferInstall(storage ?? { getItem: () => null }, Date.now(), isStandalone)) return;

    let deferredPrompt = null;
    const accept = box.querySelector('[data-install-accept]');
    const iosHint = box.querySelector('[data-install-ios]');
    const hide = () => { box.hidden = true; };
    const show = (mode) => {
        accept.hidden = mode === 'ios';
        iosHint.hidden = mode !== 'ios';
        setTimeout(() => { box.hidden = false; }, SHOW_DELAY_MS);
    };

    window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault();
        deferredPrompt = event;
        show('install');
    });
    window.addEventListener('appinstalled', hide);
    if (isIosSafari(navigator.userAgent)) show('ios');

    box.querySelectorAll('[data-install-dismiss]').forEach((button) => button.addEventListener('click', () => {
        try {
            storage?.setItem(INSTALL_DISMISS_KEY, String(Date.now()));
        } catch {
            // Without storage the prompt simply returns on the next visit.
        }
        hide();
    }));
    accept.addEventListener('click', async () => {
        if (!deferredPrompt) return;
        deferredPrompt.prompt();
        await deferredPrompt.userChoice.catch(() => null);
        deferredPrompt = null;
        hide();
    });
}
