import { readProfile, withProfile } from './profile.js';

export const MINIMUM_HEROES = 3;

export const formatRupiah = (amount) => `Rp ${amount.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.')}`;

export const countHeroes = (text) => text.split(/[,\n]/).map((hero) => hero.trim()).filter(Boolean).length;

/** The password is deliberately never part of the message, so it cannot end up in a URL. */
export function buildOrderMessage({ rank, price, stars, packageName, packagePrice, loginVia, userId, loginId, heroes, notes }) {
    const order = packageName
        ? [`Paket: ${packageName}`, `Total: ${formatRupiah(packagePrice)}`]
        : [`Rank saat ini: ${rank} (${formatRupiah(price)}/bintang)`, `Jumlah bintang: ${stars}`, `Total: ${formatRupiah(price * stars)}`];

    return [
        'Halo admin, saya ingin memesan joki Mobile Legends.',
        ...order,
        `Login via: ${loginVia}`,
        `User ID & Nick Name: ${userId}`,
        `Email/No. HP/Moonton ID: ${loginId}`,
        'Password: (tempel di sini)',
        `Request hero: ${heroes}`,
        notes && `Catatan: ${notes}`,
    ].filter(Boolean).join('\n');
}

export function initialiseJokiOrder() {
    const form = document.querySelector('[data-joki-form]');
    if (!form) return;
    const { rank, stars, loginVia, userId, loginId, password, heroes, notes } = form.elements;
    const packageSelect = form.elements.package;
    const total = form.querySelector('[data-joki-total]');
    const status = form.querySelector('[data-joki-status]');
    const price = () => Number(rank.selectedOptions[0].dataset.price);
    const packagePrice = () => Number(packageSelect.selectedOptions[0].dataset.price);
    const showTotal = () => {
        const isPackage = packageSelect.value !== '';
        form.querySelectorAll('[data-joki-per-star]').forEach((field) => { field.hidden = isPackage; });
        rank.disabled = isPackage;
        stars.disabled = isPackage;
        const count = Number(stars.value);
        total.textContent = isPackage ? formatRupiah(packagePrice()) : (count > 0 ? formatRupiah(price() * count) : '-');
    };
    const announce = (message) => {
        status.textContent = message;
        status.classList.remove('hidden');
    };

    document.addEventListener('click', (event) => {
        const rankLink = event.target.closest('[data-joki-rank]');
        const packageLink = event.target.closest('[data-joki-package]');
        if (rankLink) {
            packageSelect.value = '';
            rank.value = rankLink.dataset.jokiRank;
        } else if (packageLink) {
            packageSelect.value = packageLink.dataset.jokiPackage;
        } else {
            return;
        }
        showTotal();
    });
    packageSelect.addEventListener('change', showTotal);
    rank.addEventListener('change', showTotal);
    stars.addEventListener('input', showTotal);
    heroes.addEventListener('input', () => heroes.setCustomValidity(''));
    showTotal();

    form.querySelector('[data-joki-submit]').addEventListener('click', () => {
        heroes.setCustomValidity(countHeroes(heroes.value) >= MINIMUM_HEROES ? '' : `Masukkan minimal ${MINIMUM_HEROES} hero, pisahkan dengan koma.`);
        if (!form.reportValidity()) return;

        const message = buildOrderMessage({
            rank: rank.value,
            price: price(),
            stars: Number(stars.value),
            packageName: packageSelect.value,
            packagePrice: packageSelect.value ? packagePrice() : 0,
            loginVia: loginVia.value,
            userId: userId.value.trim(),
            loginId: loginId.value.trim(),
            heroes: heroes.value.trim(),
            notes: notes.value.trim(),
        });
        let profile = { name: '', phone: '' };
        try {
            profile = readProfile(window.localStorage);
        } catch {
            // Storage can be blocked; the order still works without the saved profile.
        }
        const url = withProfile(`${form.dataset.whatsappUrl}?text=${encodeURIComponent(message)}`, profile);

        const passwordFailed = () => announce('Ketik password langsung di chat WhatsApp pada baris Password.');
        if (navigator.clipboard) {
            navigator.clipboard.writeText(password.value).then(() => {
                password.value = '';
                announce('Password sudah disalin. Tempel di chat WhatsApp pada baris Password.');
            }, passwordFailed);
        } else {
            passwordFailed();
        }

        const opened = window.open(url, '_blank');
        if (opened) {
            opened.opener = null;
        } else {
            window.location.href = url;
        }
    });
}
