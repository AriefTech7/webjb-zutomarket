// Instalment simulator on /cicilan: price → DP, balance and payoff deadline, using the store's tiers.
import { formatThousands } from './money.js';

export const formatRupiah = (amount) => `Rp ${formatThousands(Math.round(amount))}`;

/** Days to pay off an account of this price: the first tier whose "max" covers it (null max = and above). */
export function dueDaysFor(tiers, price) {
    const tier = tiers.find((item) => item.max === null || price <= item.max);
    return tier ? tier.days : null;
}

export function simulate(price, percent, tiers) {
    const dp = Math.ceil(price * (percent ?? 0) / 100);
    return { total: price, dp, remaining: Math.max(0, price - dp), days: dueDaysFor(tiers, price) };
}

export function initialiseInstallmentSimulator() {
    const box = document.querySelector('[data-installment-sim]');
    if (!box) return;
    const percent = Number(box.dataset.percent) || null;
    const tiers = JSON.parse(box.dataset.tiers);
    const input = box.querySelector('input[name="harga"]');
    const out = (name) => box.querySelector(`[data-sim="${name}"]`);
    const render = () => {
        const price = Number(input.value.replace(/\D/g, ''));
        const result = simulate(price, percent, tiers);
        out('total').textContent = price ? formatRupiah(result.total) : '-';
        if (out('dp')) out('dp').textContent = price ? formatRupiah(result.dp) : '-';
        out('days').textContent = price && result.days ? `${result.days} hari` : '-';
        out('note').textContent = !price
            ? 'Masukkan harga akun untuk melihat simulasinya.'
            : `Sisa pembayaran ${formatRupiah(result.remaining)} wajib dilunasi${result.days ? ` maksimal ${result.days} hari` : ''} setelah DP masuk.`;
        // Highlight the "Aturan cicilan" row that applies to this price.
        const active = price ? tiers.findIndex((tier) => tier.max === null || price <= tier.max) : -1;
        document.querySelectorAll('[data-tier-index]').forEach((row) => {
            row.toggleAttribute('data-active', Number(row.dataset.tierIndex) === active);
        });
    };
    input.addEventListener('input', render);
    render();
}
