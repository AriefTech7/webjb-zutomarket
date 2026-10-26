export const WISHLIST_KEY = 'game-market-wishlist';
export const WISHLIST_LIMIT = 100;

const validId = (id) => Number.isSafeInteger(id) && id > 0;
const normalizeIds = (ids) => Array.isArray(ids)
    ? [...new Set(ids.filter(validId))].slice(0, WISHLIST_LIMIT)
    : [];

export function createWishlistStore(storage) {
    const ids = () => {
        const raw = storage.getItem(WISHLIST_KEY);
        try {
            return normalizeIds(JSON.parse(raw ?? '[]'));
        } catch {
            return [];
        }
    };
    const replace = (values) => {
        const next = normalizeIds(values);
        storage.setItem(WISHLIST_KEY, JSON.stringify(next));
        return next;
    };
    const toggle = (id) => {
        if (!validId(id)) throw new TypeError('ID akun tidak valid.');
        const current = ids();
        if (current.includes(id)) return replace(current.filter(value => value !== id));
        if (current.length >= WISHLIST_LIMIT) throw new RangeError('Wishlist maksimal 100 akun. Hapus salah satu akun terlebih dahulu.');
        return replace([id, ...current]);
    };

    return { ids, replace, toggle };
}

export async function fetchWishlist(ids, endpoint, fetcher = fetch, signal) {
    const url = new URL(endpoint, globalThis.location?.href ?? 'http://localhost');
    ids.forEach(id => url.searchParams.append('ids[]', id));
    const response = await fetcher(url, { headers: { Accept: 'application/json' }, cache: 'no-store', signal });
    if (!response.ok) throw new Error('Wishlist belum dapat dimuat. Pilihanmu tetap tersimpan. Coba lagi.');
    const result = await response.json();
    if (!Array.isArray(result.ids) || result.ids.some(id => !validId(id) || !ids.includes(id))
        || new Set(result.ids).size !== result.ids.length || typeof result.html !== 'string') {
        throw new Error('Respons wishlist tidak valid. Pilihanmu tetap tersimpan.');
    }
    return result;
}

export function initialiseWishlist() {
    const page = document.querySelector('[data-wishlist-page]');
    const grid = page?.querySelector('[data-wishlist-grid]');
    const loading = page?.querySelector('[data-wishlist-loading]');
    const empty = page?.querySelector('[data-wishlist-empty]');
    const errorPanel = page?.querySelector('[data-wishlist-error]');
    const feedback = document.querySelector('[data-wishlist-feedback]');
    const storageError = 'Penyimpanan browser tidak tersedia. Izinkan penyimpanan situs agar wishlist dapat digunakan.';
    const announce = (message) => {
        if (!feedback) return;
        feedback.textContent = message;
        feedback.classList.remove('hidden');
    };
    const showError = (message) => {
        if (!page) return;
        errorPanel.querySelector('[data-wishlist-error-message]').textContent = message;
        errorPanel.classList.remove('hidden');
        loading.classList.add('hidden');
        empty.classList.add('hidden');
        grid.setAttribute('aria-busy', 'false');
    };

    let store;
    try {
        store = createWishlistStore(window.localStorage);
        store.ids();
    } catch {
        announce(storageError);
        showError(storageError);
        return;
    }

    const synchronise = () => {
        const ids = store.ids();
        document.querySelectorAll('[data-wishlist-count]').forEach(counter => { counter.textContent = ids.length; });
        document.querySelectorAll('[data-wishlist-toggle]').forEach(button => {
            const saved = ids.includes(Number(button.dataset.wishlistToggle));
            button.disabled = false;
            button.setAttribute('aria-pressed', String(saved));
            button.setAttribute('aria-label', `${saved ? 'Hapus' : 'Simpan'} ${button.dataset.accountTitle} ${saved ? 'dari' : 'ke'} wishlist`);
            button.querySelector('[data-wishlist-heart]')?.setAttribute('fill', saved ? 'currentColor' : 'none');
            const label = button.querySelector('[data-wishlist-label]');
            if (label) label.textContent = saved ? 'Tersimpan di wishlist' : 'Simpan ke wishlist';
        });
    };

    let requestNumber = 0;
    let controller;
    const loadPage = async () => {
        if (!page) return;
        const currentRequest = ++requestNumber;
        controller?.abort();
        controller = new AbortController();
        errorPanel.classList.add('hidden');
        empty.classList.add('hidden');
        loading.classList.remove('hidden');
        grid.setAttribute('aria-busy', 'true');

        try {
            const ids = store.ids();
            if (!ids.length) {
                grid.replaceChildren();
                empty.classList.remove('hidden');
                synchronise();
                return;
            }
            const result = await fetchWishlist(ids, page.dataset.wishlistEndpoint, window.fetch.bind(window), controller.signal);
            if (currentRequest !== requestNumber || JSON.stringify(store.ids()) !== JSON.stringify(ids)) return;
            if (JSON.stringify(result.ids) !== JSON.stringify(ids)) store.replace(result.ids);
            grid.innerHTML = result.html;
            empty.classList.toggle('hidden', result.ids.length > 0);
            synchronise();
        } catch (error) {
            if (currentRequest !== requestNumber || error.name === 'AbortError') return;
            showError(error.name === 'SecurityError' || error.name === 'QuotaExceededError'
                ? storageError : 'Wishlist belum dapat dimuat. Pilihanmu tetap tersimpan. Coba lagi.');
        } finally {
            if (currentRequest === requestNumber) {
                loading.classList.add('hidden');
                grid.setAttribute('aria-busy', 'false');
            }
        }
    };

    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-wishlist-toggle]');
        if (!button || button.disabled) return;
        try {
            const id = Number(button.dataset.wishlistToggle);
            const ids = store.toggle(id);
            synchronise();
            announce(ids.includes(id) ? 'Akun ditambahkan ke wishlist.' : 'Akun dihapus dari wishlist.');
            void loadPage();
        } catch (error) {
            announce(error instanceof RangeError ? error.message : storageError);
        }
    });
    page?.querySelector('[data-wishlist-retry]')?.addEventListener('click', () => { void loadPage(); });
    const refresh = () => {
        try {
            synchronise();
            void loadPage();
        } catch {
            announce(storageError);
            showError(storageError);
        }
    };
    window.addEventListener('storage', (event) => {
        if (event.key === WISHLIST_KEY || event.key === null) refresh();
    });
    window.addEventListener('pageshow', (event) => { if (event.persisted) refresh(); });
    document.addEventListener('visibilitychange', () => { if (document.visibilityState === 'visible') refresh(); });
    refresh();
}
