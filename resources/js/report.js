// /laporan: "Buat Laporan" opens the report dialog; the category decides which suspect field is required.
export const MAX_EVIDENCE = 10;

export function evidenceError(files, maxBytes = 10 * 1024 * 1024) {
    if (files.length === 0) return 'Upload minimal 1 gambar bukti.';
    if (files.length > MAX_EVIDENCE) return `Maksimal ${MAX_EVIDENCE} gambar bukti.`;
    if ([...files].some((file) => file.size > maxBytes)) return 'Setiap gambar bukti maksimal 10 MB.';
    return '';
}

export function initialiseReportForm() {
    const dialog = document.querySelector('[data-report-dialog]');
    if (!dialog || typeof dialog.showModal !== 'function') return;
    const form = dialog.querySelector('[data-report-form]');
    const gameIdBox = form.querySelector('[data-report-game-id]');
    const gameId = gameIdBox.querySelector('input');
    const waNumber = form.elements.wa_number;
    const waOptional = form.querySelector('[data-report-wa-optional]');
    const evidence = form.querySelector('[data-report-evidence]');

    const syncCategory = () => {
        const isGameId = form.elements.category.value === 'id_game';
        gameIdBox.hidden = !isGameId;
        gameId.disabled = !isGameId;
        gameId.required = isGameId;
        waNumber.required = !isGameId;
        waOptional.hidden = !isGameId;
    };

    document.querySelectorAll('[data-report-open]').forEach((button) => button.addEventListener('click', () => dialog.showModal()));
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog || event.target.closest('[data-report-close]')) dialog.close();
    });
    form.addEventListener('change', (event) => {
        if (event.target.name === 'category') syncCategory();
        if (event.target === evidence) evidence.setCustomValidity(evidenceError(evidence.files));
    });
    syncCategory();

    // Sent back with errors, or linked as /laporan#buat: open the form straight away.
    if (dialog.hasAttribute('data-open-on-load') || window.location.hash === '#buat') dialog.showModal();
}
