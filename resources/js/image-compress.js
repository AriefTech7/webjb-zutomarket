// Big photos are shrunk in the browser before upload, so a 20 MB phone screenshot still goes through quickly and stays
// under the server limits (10 MB per image, 48 MB per form). Applies to every <input type="file" accept="image/...">.
export const COMPRESS_ABOVE = 2.5 * 1024 * 1024;
export const MAX_SIDE = 2560;
const TARGET_BYTES = 2.5 * 1024 * 1024;
const QUALITIES = [0.9, 0.82, 0.74, 0.66];

/** Scale (width, height) so the longest side is at most maxSide; never enlarges. */
export function fitWithin(width, height, maxSide = MAX_SIDE) {
    const scale = Math.min(1, maxSide / Math.max(width, height));
    return [Math.max(1, Math.round(width * scale)), Math.max(1, Math.round(height * scale))];
}

/** "18,4 MB" / "820 KB" */
export function formatBytes(bytes) {
    return bytes >= 1024 * 1024
        ? `${(bytes / 1024 / 1024).toFixed(1).replace('.', ',')} MB`
        : `${Math.max(1, Math.round(bytes / 1024))} KB`;
}

let webpSupport;
function canEncodeWebp() {
    if (webpSupport === undefined) {
        const canvas = document.createElement('canvas');
        canvas.width = canvas.height = 1;
        webpSupport = canvas.toDataURL('image/webp').startsWith('data:image/webp');
    }
    return webpSupport;
}

const toBlob = (canvas, type, quality) => new Promise((resolve) => canvas.toBlob(resolve, type, quality));

/** Returns a smaller copy of a large image, or the original file when it is small, not an image, or cannot be decoded. */
export async function compressImage(file) {
    if (!file.type.startsWith('image/') || file.type === 'image/gif' || file.size <= COMPRESS_ABOVE) return file;
    let bitmap;
    try {
        bitmap = await createImageBitmap(file);
    } catch {
        return file;
    }
    const [width, height] = fitWithin(bitmap.width, bitmap.height);
    const canvas = document.createElement('canvas');
    canvas.width = width;
    canvas.height = height;
    const context = canvas.getContext('2d');
    // WebP keeps transparency (PNG banners); without it, JPEG on a white background.
    const type = canEncodeWebp() ? 'image/webp' : 'image/jpeg';
    if (type === 'image/jpeg') {
        context.fillStyle = '#ffffff';
        context.fillRect(0, 0, width, height);
    }
    context.imageSmoothingQuality = 'high';
    context.drawImage(bitmap, 0, 0, width, height);
    bitmap.close?.();

    let blob = null;
    for (const quality of QUALITIES) {
        blob = await toBlob(canvas, type, quality);
        if (!blob || blob.size <= TARGET_BYTES) break;
    }
    if (!blob || blob.size >= file.size) return file;
    const name = file.name.replace(/\.[^.]+$/, '') + (type === 'image/webp' ? '.webp' : '.jpg');
    return new File([blob], name, { type, lastModified: Date.now() });
}

/**
 * Profile photo: centre-cropped square, resized to size×size, JPEG (the server cannot read WebP). Returns the original
 * file when it cannot be decoded.
 */
export async function squareImage(file, size = 256) {
    if (!file.type.startsWith('image/')) return file;
    let bitmap;
    try {
        bitmap = await createImageBitmap(file);
    } catch {
        return file;
    }
    const side = Math.min(bitmap.width, bitmap.height);
    const canvas = document.createElement('canvas');
    canvas.width = canvas.height = Math.min(size, side);
    const context = canvas.getContext('2d');
    context.fillStyle = '#ffffff';
    context.fillRect(0, 0, canvas.width, canvas.height);
    context.imageSmoothingQuality = 'high';
    context.drawImage(bitmap, (bitmap.width - side) / 2, (bitmap.height - side) / 2, side, side, 0, 0, canvas.width, canvas.height);
    bitmap.close?.();
    const blob = await toBlob(canvas, 'image/jpeg', 0.9);
    if (!blob) return file;
    return new File([blob], file.name.replace(/\.[^.]+$/, '') + '.jpg', { type: 'image/jpeg', lastModified: Date.now() });
}

export function initialiseImageCompression() {
    if (typeof DataTransfer === 'undefined' || typeof createImageBitmap !== 'function') return;
    const pending = new Map();

    document.addEventListener('change', (event) => {
        const input = event.target;
        if (event.fromCompressor || !(input instanceof HTMLInputElement) || input.type !== 'file' || !input.accept.includes('image') || input.dataset.compressing) return;
        const files = [...input.files];
        // Profile photo inputs are always cropped; other image inputs only when a file is big.
        const isAvatar = input.hasAttribute('data-avatar-input');
        if (!files.length || (!isAvatar && !files.some((file) => file.size > COMPRESS_ABOVE))) return;

        let note = input.parentElement.querySelector('[data-compress-note]');
        if (!note) {
            note = document.createElement('p');
            note.dataset.compressNote = '';
            note.className = 'mt-2 text-xs text-lime-300';
            note.setAttribute('role', 'status');
            input.after(note);
        }
        note.textContent = isAvatar ? 'Menyiapkan foto…' : 'Mengompres gambar besar…';
        input.dataset.compressing = '1';
        const job = Promise.all(files.map(isAvatar ? (file) => squareImage(file) : compressImage)).then((compressed) => {
            const transfer = new DataTransfer();
            compressed.forEach((file) => transfer.items.add(file));
            input.files = transfer.files;
            const before = files.reduce((sum, file) => sum + file.size, 0);
            const after = compressed.reduce((sum, file) => sum + file.size, 0);
            const changed = compressed.filter((file, index) => file !== files[index]).length;
            note.textContent = isAvatar
                ? 'Foto dipotong persegi dan siap disimpan.'
                : (changed ? `${changed} gambar besar dikompres otomatis: ${formatBytes(before)} → ${formatBytes(after)}.` : '');
            const preview = isAvatar && input.form?.querySelector('[data-avatar-preview]');
            if (preview && compressed[0]) {
                preview.src = URL.createObjectURL(compressed[0]);
                preview.hidden = false;
                input.form.querySelector('[data-avatar-initial]')?.setAttribute('hidden', '');
            }
            delete input.dataset.compressing;
            // Let other checks (e.g. the report form) see the new files; flagged so this handler skips it.
            const refreshed = new Event('change', { bubbles: true });
            refreshed.fromCompressor = true;
            input.dispatchEvent(refreshed);
        }).finally(() => {
            delete input.dataset.compressing;
            pending.delete(input);
        });
        pending.set(input, job);
    });

    // Submitting while a photo is still being compressed: wait for it, then submit again.
    document.addEventListener('submit', (event) => {
        const jobs = [...pending.entries()].filter(([input]) => input.form === event.target).map(([, job]) => job);
        if (!jobs.length) return;
        event.preventDefault();
        const submitter = event.submitter;
        Promise.allSettled(jobs).then(() => event.target.requestSubmit(submitter ?? undefined));
    }, true);
}
