import { readProfile } from './profile.js';

export const MINIMUM_HEROES = 3;
const DIVISIONS = ['V', 'IV', 'III', 'II', 'I'];
const STARS_PER_DIVISION = 5;

export const formatRupiah = (amount) => `Rp ${Math.round(amount).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.')}`;

export const countHeroes = (text) => text.split(/[,\n]/).map((hero) => hero.trim()).filter(Boolean).length;

/**
 * Every star from Epic V upwards gets one position on a single ladder, so the stars between two ranks are a
 * subtraction. Epic/Legend divisions hold stars 0-4; the Mythic tiers hold their own range of Mythic stars.
 */
export function rankOptions(tiers) {
    const divisionStars = tiers.filter((tier) => tier.divisions).reduce((sum, tier) => sum + tier.divisions * STARS_PER_DIVISION, 0);
    let base = 0;
    return tiers.flatMap((tier, tierIndex) => {
        if (!tier.divisions) {
            return [{ id: tier.name, label: tier.name, tier: tierIndex, base: divisionStars, min: tier.from, max: tier.to, mythic: true }];
        }
        const divisions = DIVISIONS.slice(DIVISIONS.length - tier.divisions).map((division, index) => ({
            id: `${tier.name} ${division}`,
            label: `${tier.name} ${division}`,
            tier: tierIndex,
            base: base + index * STARS_PER_DIVISION,
            min: 0,
            max: STARS_PER_DIVISION - 1,
            mythic: false,
        }));
        base += tier.divisions * STARS_PER_DIVISION;
        return divisions;
    });
}

/** Division ranks count stars 0-4 from their own base; Mythic tiers count absolute Mythic stars from the Mythic base. */
export const ladderPosition = (option, stars) => option.base + stars;

/** The tier whose price applies to the star climbed from this ladder position. */
export function tierAt(tiers, position) {
    let start = 0;
    const divisionStars = tiers.filter((tier) => tier.divisions).reduce((sum, tier) => sum + tier.divisions * STARS_PER_DIVISION, 0);
    for (const tier of tiers) {
        if (tier.divisions) {
            const end = start + tier.divisions * STARS_PER_DIVISION;
            if (position < end) return tier;
            start = end;
        } else if (position - divisionStars <= tier.to) {
            return tier;
        }
    }
    return tiers[tiers.length - 1];
}

/** Stars to climb and their price, split per tier. Returns zero stars when the target is not above the start. */
export function quote(tiers, from, to, extraPercent = 0) {
    const lines = [];
    for (let position = from; position < to; position++) {
        const tier = tierAt(tiers, position);
        const line = lines.find((item) => item.name === tier.name);
        if (line) line.stars++;
        else lines.push({ name: tier.name, stars: 1, price: tier.price });
    }
    const subtotal = lines.reduce((sum, line) => sum + line.stars * line.price, 0);
    const extra = Math.round(subtotal * extraPercent / 100);
    return { stars: Math.max(0, to - from), lines, subtotal, extra, total: subtotal + extra };
}

export const describeRank = (option, stars) => (option.mythic ? `${option.label} (${stars} bintang Mythic)` : `${option.label} bintang ${stars}`);

/** The password is deliberately never part of the message, so it cannot end up in a URL. */
export function buildOrderMessage(order) {
    const lines = ['Halo admin, saya ingin memesan joki Mobile Legends.', ''];
    if (order.mode === 'package') {
        lines.push('Paket:', ...order.packages.map((item) => `- ${item.name} (${formatRupiah(item.price)})`));
        lines.push(`Total: ${formatRupiah(order.packages.reduce((sum, item) => sum + item.price, 0))}`);
    } else {
        lines.push(`Layanan: ${order.service.label}`);
        lines.push(`Rank: ${order.from} → ${order.to} (${order.quote.stars} bintang)`);
        lines.push(...order.quote.lines.map((line) => `- ${line.name}: ${line.stars} × ${formatRupiah(line.price)}`));
        if (order.quote.extra) lines.push(`- Biaya ${order.service.label}: ${formatRupiah(order.quote.extra)}`);
        lines.push(`Total: ${formatRupiah(order.quote.total)}`);
        if (order.role) lines.push(`Role prioritas: ${order.role}`);
    }
    if (order.heroes?.length) lines.push(`Request hero: ${order.heroes.join(', ')}`);
    lines.push('', 'Data akun:', `ID: ${order.userId}${order.serverId ? ` (${order.serverId})` : ''}`);
    if (order.needsLogin) {
        lines.push(`Masuk melalui: ${order.loginVia}`, `Email/No. HP/Moonton ID: ${order.loginId}`, 'Password: (tempel di sini)');
    }
    if (order.notes) lines.push(`Catatan: ${order.notes}`);
    lines.push('', 'Kontak:', `Nama: ${order.contact.name}`, `WhatsApp: ${order.contact.phone}`);
    if (order.contact.email) lines.push(`Email: ${order.contact.email}`);
    return lines.join('\n');
}

/** "0812..", "62812..", "+62 812-.." → "812..": the field already shows the +62 prefix. */
export const localPhone = (phone) => phone.replace(/\D+/g, '').replace(/^(?:62|0)/, '');

export function initialiseJokiOrder() {
    const form = document.querySelector('[data-joki-form]');
    if (!form) return;
    const tiers = JSON.parse(form.dataset.jokiTiers);
    const services = JSON.parse(form.dataset.jokiServices);
    const ranks = rankOptions(tiers);
    const els = form.elements;
    const estimate = form.querySelector('[data-joki-estimate]');
    const status = form.querySelector('[data-joki-status]');
    const heroList = form.querySelector('[data-joki-heroes]');
    // "/joki-ml#paket" opens the package tab, so it can be linked from elsewhere.
    let mode = window.location.hash === '#paket' ? 'package' : 'calculator';
    let packages = [];

    const rankById = (id) => ranks.find((rank) => rank.id === id) ?? ranks[0];
    [els.fromRank, els.toRank].forEach((select) => {
        select.replaceChildren(...ranks.map((rank) => new Option(rank.label, rank.id)));
    });
    els.toRank.value = ranks[1]?.id ?? ranks[0].id;

    const clampStars = (select, input) => {
        const rank = rankById(select.value);
        input.min = rank.min;
        input.max = rank.max;
        const value = Number(input.value);
        input.value = String(Number.isFinite(value) ? Math.min(rank.max, Math.max(rank.min, value)) : rank.min);
        form.querySelectorAll(`[data-step^="${input.name}:"]`).forEach((button) => {
            const step = Number(button.dataset.step.split(':')[1]);
            button.disabled = step < 0 ? Number(input.value) <= rank.min : Number(input.value) >= rank.max;
        });
    };
    const currentQuote = () => {
        const from = rankById(els.fromRank.value);
        const to = rankById(els.toRank.value);
        const service = services[els.service.value] ?? services.regular;
        return {
            from, to, service,
            result: quote(tiers, ladderPosition(from, Number(els.fromStars.value)), ladderPosition(to, Number(els.toStars.value)), service.extra),
        };
    };

    const row = (label, value, strong = false) => {
        const item = document.createElement('div');
        item.className = `flex items-start justify-between gap-3 ${strong ? 'border-t border-white/10 pt-3 text-base font-black' : 'text-sm'}`;
        const name = document.createElement('span');
        name.className = strong ? 'text-white' : 'text-slate-400';
        name.textContent = label;
        const amount = document.createElement('span');
        amount.className = strong ? 'text-lime-300' : 'text-right font-semibold text-white';
        amount.textContent = value;
        item.append(name, amount);
        return item;
    };
    const emptyState = (title, text) => {
        const wrap = document.createElement('div');
        const heading = document.createElement('p');
        heading.className = 'font-bold text-white';
        heading.textContent = title;
        const body = document.createElement('p');
        body.className = 'muted mt-1';
        body.textContent = text;
        wrap.append(heading, body);
        return wrap;
    };
    const renderEstimate = () => {
        const nodes = [];
        if (mode === 'package') {
            if (!packages.length) {
                nodes.push(emptyState('Daftar paketmu kosong.', 'Tambahkan paket untuk melanjutkan.'));
            } else {
                packages.forEach((item) => {
                    const line = row(item.name, formatRupiah(item.price));
                    const remove = document.createElement('button');
                    remove.type = 'button';
                    remove.className = 'ml-2 text-xs font-bold text-rose-300 hover:underline';
                    remove.textContent = 'Hapus';
                    remove.dataset.jokiRemovePackage = item.name;
                    line.lastChild.append(remove);
                    nodes.push(line);
                });
                nodes.push(row('Total', formatRupiah(packages.reduce((sum, item) => sum + item.price, 0)), true));
            }
        } else {
            const { from, to, service, result } = currentQuote();
            if (!result.stars) {
                nodes.push(emptyState('Rank tujuan harus lebih tinggi.', 'Pilih rank tujuan dan bintang di atas rank saat ini.'));
            } else {
                nodes.push(row('Dari', describeRank(from, Number(els.fromStars.value))), row('Ke', describeRank(to, Number(els.toStars.value))), row('Layanan', service.label));
                result.lines.forEach((line) => nodes.push(row(`${line.name} · ${line.stars} bintang`, formatRupiah(line.stars * line.price))));
                if (result.extra) nodes.push(row(`Biaya ${service.label}`, formatRupiah(result.extra)));
                nodes.push(row(`Total (${result.stars} bintang)`, formatRupiah(result.total), true));
            }
        }
        estimate.replaceChildren(...nodes);
        const total = mode === 'package' ? packages.reduce((sum, item) => sum + item.price, 0) : currentQuote().result.total;
        form.querySelectorAll('[data-joki-total]').forEach((element) => { element.textContent = total ? formatRupiah(total) : '-'; });
        form.querySelectorAll('[data-joki-add-package]').forEach((button) => {
            const added = packages.some((item) => item.name === button.dataset.jokiAddPackage);
            button.setAttribute('aria-pressed', String(added));
            button.textContent = added ? 'Ditambahkan ✓' : 'Tambah';
        });
    };

    const setSection = (name, visible) => {
        form.querySelectorAll(`[data-joki-section="${name}"]`).forEach((section) => {
            section.hidden = !visible;
            section.disabled = !visible;
        });
    };
    const update = () => {
        clampStars(els.fromRank, els.fromStars);
        clampStars(els.toRank, els.toStars);
        form.querySelectorAll('[data-joki-rank-icon]').forEach((icon) => {
            icon.src = tiers[rankById(els[icon.dataset.jokiRankIcon].value).tier].icon;
        });
        const service = els.service.value;
        setSection('calculator', mode === 'calculator');
        setSection('package', mode === 'package');
        setSection('role', mode === 'calculator' && service === 'role');
        setSection('heroes', mode === 'package' || service !== 'gendong');
        setSection('login', mode === 'package' || service !== 'gendong');
        form.querySelectorAll('[data-joki-tab]').forEach((tab) => {
            const active = tab.dataset.jokiTab === mode;
            tab.setAttribute('aria-selected', String(active));
            tab.tabIndex = active ? 0 : -1;
        });
        renderEstimate();
    };

    const addHeroField = () => {
        const input = document.createElement('input');
        input.className = 'field';
        input.type = 'text';
        input.name = 'heroes[]';
        input.maxLength = 40;
        input.placeholder = `Hero ${heroList.children.length + 1}`;
        input.setAttribute('aria-label', input.placeholder);
        heroList.append(input);
        return input;
    };

    form.addEventListener('click', (event) => {
        const tab = event.target.closest('[data-joki-tab]');
        const step = event.target.closest('[data-step]');
        const add = event.target.closest('[data-joki-add-package]');
        const remove = event.target.closest('[data-joki-remove-package]');
        if (tab) {
            mode = tab.dataset.jokiTab;
            history.replaceState(null, '', mode === 'package' ? '#paket' : window.location.pathname + window.location.search);
        } else if (step) {
            const [name, delta] = step.dataset.step.split(':');
            els[name].value = String(Number(els[name].value) + Number(delta));
        } else if (add) {
            const name = add.dataset.jokiAddPackage;
            packages = packages.some((item) => item.name === name)
                ? packages.filter((item) => item.name !== name)
                : [...packages, { name, price: Number(add.dataset.price) }];
        } else if (remove) {
            packages = packages.filter((item) => item.name !== remove.dataset.jokiRemovePackage);
        } else if (event.target.closest('[data-joki-add-hero]')) {
            if (heroList.children.length < 10) addHeroField().focus();
            return;
        } else if (event.target.closest('[data-joki-continue]')) {
            form.querySelector('#joki-detail').scrollIntoView({ behavior: 'smooth', block: 'start' });
            form.querySelector('#joki-detail input:not([disabled])')?.focus({ preventScroll: true });
            return;
        } else {
            return;
        }
        update();
    });
    form.addEventListener('change', update);
    form.addEventListener('input', (event) => {
        if (event.target.name === 'fromStars' || event.target.name === 'toStars') update();
        if (event.target.name === 'heroes[]') heroList.querySelector('input')?.setCustomValidity('');
    });

    let profile = { name: '', phone: '' };
    try {
        profile = readProfile(window.localStorage);
    } catch {
        // Storage can be blocked; the form simply starts empty.
    }
    if (!els.contactName.value) els.contactName.value = profile.name;
    if (!els.contactPhone.value) els.contactPhone.value = localPhone(profile.phone);
    update();

    const announce = (message) => {
        status.textContent = message;
        status.classList.remove('hidden');
    };

    form.querySelector('[data-joki-submit]').addEventListener('click', () => {
        const { from, to, service, result } = currentQuote();
        const heroes = [...form.querySelectorAll('input[name="heroes[]"]')].map((input) => input.value.trim()).filter(Boolean);
        const firstHero = heroList.querySelector('input');
        const needsHeroes = mode === 'package' || ['regular', 'hero'].includes(els.service.value);
        firstHero?.setCustomValidity(needsHeroes && heroes.length < MINIMUM_HEROES ? `Isi minimal ${MINIMUM_HEROES} hero.` : '');
        if (mode === 'package' && !packages.length) {
            announce('Tambahkan minimal satu paket joki dulu.');
            form.querySelector('[data-joki-panel="package"]').scrollIntoView({ behavior: 'smooth' });
            return;
        }
        if (mode === 'calculator' && !result.stars) {
            announce('Rank tujuan harus lebih tinggi dari rank saat ini.');
            els.toRank.focus();
            return;
        }
        if (!form.reportValidity()) return;

        const needsLogin = !els.login.disabled;
        const message = buildOrderMessage({
            mode,
            service,
            from: describeRank(from, Number(els.fromStars.value)),
            to: describeRank(to, Number(els.toStars.value)),
            quote: result,
            packages,
            role: mode === 'calculator' && els.service.value === 'role' ? els.role.value : '',
            heroes,
            userId: els.userId.value.trim(),
            serverId: els.serverId.value.trim(),
            needsLogin,
            loginVia: els.loginVia.value,
            loginId: els.loginId.value.trim(),
            notes: els.notes.value.trim(),
            contact: { name: els.contactName.value.trim(), phone: `+62${localPhone(els.contactPhone.value)}`, email: els.contactEmail.value.trim() },
        });
        const url = `${form.dataset.whatsappUrl}?text=${encodeURIComponent(message)}`;

        if (needsLogin) {
            const passwordFailed = () => announce('Ketik password langsung di chat WhatsApp pada baris Password.');
            if (navigator.clipboard) {
                navigator.clipboard.writeText(els.password.value).then(() => {
                    els.password.value = '';
                    announce('Password sudah disalin. Tempel di chat WhatsApp pada baris Password.');
                }, passwordFailed);
            } else {
                passwordFailed();
            }
        }

        const opened = window.open(url, '_blank');
        if (opened) {
            opened.opener = null;
        } else {
            window.location.href = url;
        }
    });
}
