// Checkout page: live total for the chosen payment channel. Order page: poll the status while waiting for
// payment, "Salin" buttons, and the "Buka live chat admin" button.
export const rupiah = (amount) => `Rp ${Math.round(amount).toLocaleString('id-ID')}`;

export function initialiseCheckout() {
    const form = document.querySelector('[data-checkout-form]');
    if (form) {
        const subtotal = Number(form.dataset.subtotal) || 0;
        const fee = form.querySelector('[data-checkout-fee]');
        const total = form.querySelector('[data-checkout-total]');
        const method = form.querySelector('[data-checkout-method]');
        const update = () => {
            const chosen = form.querySelector('input[name="method"]:checked');
            const amount = chosen ? Number(chosen.dataset.fee) || 0 : 0;
            fee.textContent = chosen ? rupiah(amount) : '–';
            method.textContent = chosen ? chosen.dataset.name : 'pembayaran';
            total.textContent = rupiah(subtotal + amount);
        };
        form.addEventListener('change', update);
        form.addEventListener('submit', () => {
            const button = form.querySelector('[data-pay-button]');
            if (button) setTimeout(() => { button.disabled = true; button.textContent = 'Membuat pembayaran…'; }, 0);
        });
        update();
    }

    document.addEventListener('click', (event) => {
        const copy = event.target.closest('[data-copy]');
        if (copy) {
            navigator.clipboard?.writeText(copy.dataset.copy).then(() => {
                const label = copy.textContent;
                copy.textContent = 'Tersalin ✓';
                setTimeout(() => { copy.textContent = label; }, 1500);
            }).catch(() => {});
        }
        if (event.target.closest('[data-open-chat]')) {
            document.querySelector('[data-chat-launcher]')?.click();
        }
    });

    const waiting = document.querySelector('[data-order-poll]');
    if (waiting) {
        const check = async () => {
            if (document.visibilityState !== 'visible') return;
            try {
                const response = await fetch(waiting.dataset.orderPoll, { headers: { Accept: 'application/json' }, cache: 'no-store' });
                if (!response.ok) return;
                const { status } = await response.json();
                if (status && status !== waiting.dataset.orderStatus) window.location.reload();
            } catch {
                // Offline for a moment: try again on the next tick.
            }
        };
        setInterval(check, 8000);
    }
}
