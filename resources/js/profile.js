export const PROFILE_KEY = 'zutomarket-profile';

export function readProfile(storage) {
    try {
        const profile = JSON.parse(storage.getItem(PROFILE_KEY) ?? '{}');

        return {
            name: typeof profile?.name === 'string' ? profile.name : '',
            phone: typeof profile?.phone === 'string' ? profile.phone : '',
        };
    } catch {
        return { name: '', phone: '' };
    }
}

export function withProfile(href, { name, phone }) {
    const lines = [name && `Nama: ${name}`, phone && `No. WhatsApp: ${phone}`].filter(Boolean);
    if (!lines.length) return href;
    const url = new URL(href);
    const text = [url.searchParams.get('text'), ...lines].filter(Boolean).join('\n');

    return `${url.origin}${url.pathname}?text=${encodeURIComponent(text)}`;
}

export function initialiseProfile() {
    let storage;
    try {
        storage = window.localStorage;
        storage.getItem(PROFILE_KEY);
    } catch {
        return;
    }

    document.addEventListener('click', (event) => {
        const link = event.target.closest('a[href^="https://wa.me/"]');
        if (!link) return;
        link.dataset.baseHref ??= link.href;
        link.href = withProfile(link.dataset.baseHref, readProfile(storage));
    });

    const form = document.querySelector('[data-profile-form]');
    if (!form) return;
    const status = document.querySelector('[data-profile-status]');
    const announce = (message) => {
        status.textContent = message;
        status.classList.remove('hidden');
    };
    const saved = readProfile(storage);
    form.elements.name.value = saved.name;
    form.elements.phone.value = saved.phone;

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        try {
            storage.setItem(PROFILE_KEY, JSON.stringify({
                name: form.elements.name.value.trim(),
                phone: form.elements.phone.value.trim(),
            }));
            announce('Profil tersimpan di browser ini.');
        } catch {
            announce('Penyimpanan browser tidak tersedia.');
        }
    });
    document.querySelector('[data-profile-clear]').addEventListener('click', () => {
        try {
            storage.removeItem(PROFILE_KEY);
            form.reset();
            announce('Profil dihapus dari browser ini.');
        } catch {
            announce('Penyimpanan browser tidak tersedia.');
        }
    });
}
