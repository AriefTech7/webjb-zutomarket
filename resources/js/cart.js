// Shopping cart: a list of account ids kept in this browser, like the guest wishlist. The server turns the
// ids into the accounts that can still be bought and a WhatsApp checkout per seller (see CartController).
export const CART_KEY = 'zutomarket-cart';
export const CART_LIMIT = 50;

const validId = (id) => Number.isSafeInteger(id) && id > 0;
const normalizeIds = (ids) => Array.isArray(ids)
    ? [...new Set(ids.filter(validId))].slice(0, CART_LIMIT)
    : [];

export function createCartStore(storage) {
    const ids = () => {
        try {
            return normalizeIds(JSON.parse(storage.getItem(CART_KEY) ?? '[]'));
        } catch {
            return [];
        }
    };
    const replace = (values) => {
        const next = normalizeIds(values);
        storage.setItem(CART_KEY, JSON.stringify(next));
        return next;
    };
    const toggle = (id) => {
        if (!validId(id)) throw new TypeError('ID akun tidak valid.');
        const current = ids();
        if (current.includes(id)) return replace(current.filter((value) => value !== id));
        if (current.length >= CART_LIMIT) throw new RangeError(`Keranjang maksimal ${CART_LIMIT} akun. Checkout atau hapus salah satu akun dulu.`);
        return replace([...current, id]);
    };

    return { ids, replace, toggle };
}

export async function fetchCart(ids, endpoint, fetcher = fetch, signal) {
    const url = new URL(endpoint, globalThis.location?.href ?? 'http://localhost');
    ids.forEach((id) => url.searchParams.append('ids[]', id));
    const response = await fetcher(url, { headers: { Accept: 'application/json' }, cache: 'no-store', signal });
    if (!response.ok) throw new Error('Keranjang belum dapat dimuat. Pilihanmu tetap tersimpan. Coba lagi.');
    const result = await response.json();
    if (!Array.isArray(result.ids) || result.ids.some((id) => !validId(id) || !ids.includes(id))
        || new Set(result.ids).size !== result.ids.length || typeof result.html !== 'string') {
        throw new Error('Respons keranjang tidak valid. Pilihanmu tetap tersimpan.');
    }
    return result;
}

export function initialiseCart() {
    const page = document.querySelector('[data-cart-page]');
    const items = page?.querySelector('[data-cart-items]');
    const loading = page?.querySelector('[data-cart-loading]');
    const empty = page?.querySelector('[data-cart-empty]');
    const errorPanel = page?.querySelector('[data-cart-error]');
    const feedback = document.querySelector('[data-wishlist-feedback]');
    const storageError = 'Penyimpanan browser tidak tersedia. Izinkan penyimpanan situs agar keranjang dapat digunakan.';
    const announce = (message) => {
        if (!feedback) return;
        feedback.textContent = message;
        feedback.classList.remove('hidden');
    };
    const showError = (message) => {
        if (!page) return;
        errorPanel.querySelector('[data-cart-error-message]').textContent = message;
        errorPanel.classList.remove('hidden');
        loading.classList.add('hidden');
        empty.classList.add('hidden');
        items.setAttribute('aria-busy', 'false');
    };

    let store;
    try {
        store = createCartStore(window.localStorage);
        store.ids();
    } catch {
        showError(storageError);
        return;
    }

    const synchronise = () => {
        const ids = store.ids();
        document.querySelectorAll('[data-cart-count]').forEach((counter) => {
            counter.textContent = ids.length;
            counter.hidden = ids.length === 0;
        });
        document.querySelectorAll('[data-cart-toggle]').forEach((button) => {
            const inCart = ids.includes(Number(button.dataset.cartToggle));
            button.disabled = false;
            button.setAttribute('aria-pressed', String(inCart));
            button.setAttribute('aria-label', `${inCart ? 'Hapus' : 'Tambahkan'} ${button.dataset.accountTitle} ${inCart ? 'dari' : 'ke'} keranjang`);
            // Card buttons are narrow: they keep the "Keranjang" label and show the state by colour. Wider buttons say it.
            const label = button.querySelector('[data-cart-label-long]');
            if (label) label.textContent = inCart ? 'Ada di keranjang ✓' : 'Tambah ke keranjang';
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
        items.setAttribute('aria-busy', 'true');

        try {
            const ids = store.ids();
            if (!ids.length) {
                items.replaceChildren();
                empty.classList.remove('hidden');
                synchronise();
                return;
            }
            const result = await fetchCart(ids, page.dataset.cartEndpoint, window.fetch.bind(window), controller.signal);
            if (currentRequest !== requestNumber || JSON.stringify(store.ids()) !== JSON.stringify(ids)) return;
            if (result.ids.length !== ids.length) {
                // Accounts sold or booked since they were added drop out of the cart.
                store.replace(result.ids);
                announce('Sebagian akun di keranjang sudah tidak tersedia dan dihapus dari keranjang.');
            }
            items.innerHTML = result.html;
            empty.classList.toggle('hidden', result.ids.length > 0);
            synchronise();
        } catch (error) {
            if (currentRequest !== requestNumber || error.name === 'AbortError') return;
            showError(error.message);
        } finally {
            if (currentRequest === requestNumber) {
                loading.classList.add('hidden');
                items.setAttribute('aria-busy', 'false');
            }
        }
    };

    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-cart-toggle]');
        if (!button || button.disabled) return;
        try {
            const id = Number(button.dataset.cartToggle);
            const ids = store.toggle(id);
            synchronise();
            announce(ids.includes(id) ? 'Akun ditambahkan ke keranjang.' : 'Akun dihapus dari keranjang.');
            void loadPage();
        } catch (error) {
            announce(error instanceof RangeError ? error.message : storageError);
        }
    });
    page?.querySelector('[data-cart-retry]')?.addEventListener('click', () => { void loadPage(); });
    const refresh = () => {
        synchronise();
        void loadPage();
    };
    window.addEventListener('storage', (event) => {
        if (event.key === CART_KEY || event.key === null) refresh();
    });
    window.addEventListener('pageshow', (event) => { if (event.persisted) refresh(); });
    refresh();
}

// Share an account: the phone's share sheet when there is one, otherwise copy the link.
export function initialiseShare() {
    document.addEventListener('click', async (event) => {
        const button = event.target.closest('[data-share-url]');
        if (!button) return;
        const { shareUrl: url, shareTitle: title } = button.dataset;
        const label = button.querySelector('[data-share-label]');
        const flash = (text) => {
            if (!label) return;
            label.textContent = text;
            setTimeout(() => { label.textContent = 'Share'; }, 2000);
        };
        if (navigator.share) {
            try {
                await navigator.share({ title, url });
                return;
            } catch (error) {
                if (error.name === 'AbortError') return;
            }
        }
        try {
            await navigator.clipboard.writeText(url);
            flash('Disalin ✓');
        } catch {
            window.prompt('Salin link akun ini:', url);
        }
    });
}
