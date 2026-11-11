/** Builds a mailto link with the feedback already filled in; spaces are encoded as %20 so mail apps show them correctly. */
export function buildFeedbackMailto(email, { type, name, message }) {
    const subject = `[${type}] Kritik & saran ZutoMarket`;
    const header = name ? [`Jenis: ${type}`, `Nama: ${name}`] : [`Jenis: ${type}`];
    const body = [...header, '', message].join('\n');

    return `mailto:${email}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
}

export function initialiseFeedbackForm() {
    const form = document.querySelector('[data-feedback-form]');
    if (!form) return;
    form.addEventListener('submit', (event) => {
        event.preventDefault();
        window.location.href = buildFeedbackMailto(form.dataset.email, {
            type: form.elements.type.value,
            name: form.elements.name.value.trim(),
            message: form.elements.message.value.trim(),
        });
    });
}
