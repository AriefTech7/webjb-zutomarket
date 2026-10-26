import { initialiseWishlist } from './wishlist';

initialiseWishlist();

document.querySelector('[data-menu-toggle]')?.addEventListener('click', (event) => {
    const menu = document.querySelector('[data-mobile-menu]');
    const open = menu.classList.toggle('hidden') === false;
    event.currentTarget.setAttribute('aria-expanded', String(open));
});

document.querySelectorAll('[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (!window.confirm(form.dataset.confirm)) event.preventDefault();
    });
});

const accountImage = document.querySelector('[data-main-image]');
let hideAccountZoom = () => {};

if (accountImage) {
    const container = accountImage.parentElement;
    const hoverPointer = window.matchMedia('(hover: hover) and (pointer: fine)');
    const lens = document.createElement('span');
    const enlargedImage = document.createElement('img');
    container.classList.add('account-image-zoom');
    lens.className = 'account-image-lens';
    lens.hidden = true;
    lens.setAttribute('aria-hidden', 'true');
    enlargedImage.alt = '';
    lens.append(enlargedImage);
    container.append(lens);
    hideAccountZoom = () => { lens.hidden = true; };

    accountImage.addEventListener('pointermove', (event) => {
        if (!hoverPointer.matches || event.pointerType === 'touch' || !accountImage.complete || !accountImage.naturalWidth) {
            hideAccountZoom();
            return;
        }

        const bounds = accountImage.getBoundingClientRect();
        const x = event.clientX - bounds.left;
        const y = event.clientY - bounds.top;
        const cover = getComputedStyle(accountImage).objectFit === 'cover';
        const scale = (cover ? Math.max : Math.min)(bounds.width / accountImage.naturalWidth, bounds.height / accountImage.naturalHeight);
        const width = accountImage.naturalWidth * scale;
        const height = accountImage.naturalHeight * scale;
        const offsetX = (bounds.width - width) / 2;
        const offsetY = (bounds.height - height) / 2;

        if (x < offsetX || x > offsetX + width || y < offsetY || y > offsetY + height) {
            hideAccountZoom();
            return;
        }

        const size = Math.min(140, bounds.width, bounds.height);
        const left = Math.max(0, Math.min(x - size / 2, bounds.width - size));
        const top = Math.max(0, Math.min(y - size / 2, bounds.height - size));
        const source = accountImage.currentSrc || accountImage.src;
        if (enlargedImage.src !== source) enlargedImage.src = source;
        lens.style.width = `${size}px`;
        lens.style.height = `${size}px`;
        lens.style.left = `${accountImage.offsetLeft + left}px`;
        lens.style.top = `${accountImage.offsetTop + top}px`;
        const zoom = 2.5;
        enlargedImage.style.width = `${width * zoom}px`;
        enlargedImage.style.height = `${height * zoom}px`;
        enlargedImage.style.left = `${size / 2 - 2 - (x - offsetX) * zoom}px`;
        enlargedImage.style.top = `${size / 2 - 2 - (y - offsetY) * zoom}px`;
        lens.hidden = false;
    });

    accountImage.addEventListener('pointerleave', hideAccountZoom);
    accountImage.addEventListener('pointercancel', hideAccountZoom);
    accountImage.addEventListener('load', hideAccountZoom);
    accountImage.addEventListener('error', hideAccountZoom);
    hoverPointer.addEventListener('change', hideAccountZoom);
    window.addEventListener('scroll', hideAccountZoom, { passive: true });
    window.addEventListener('resize', hideAccountZoom);
}

document.querySelectorAll('[data-gallery-image]').forEach((button) => {
    button.addEventListener('click', () => {
        hideAccountZoom();
        document.querySelector('[data-main-image]').src = button.dataset.galleryImage;
        document.querySelectorAll('[data-gallery-image]').forEach((item) => item.setAttribute('aria-pressed', String(item === button)));
    });
});

const tradeForm = document.querySelector('[data-trade-form]');
if (tradeForm && typeof DataTransfer !== 'undefined') {
    const input = tradeForm.querySelector('#screenshots');
    const previews = tradeForm.querySelector('[data-upload-previews]');
    const summary = tradeForm.querySelector('[data-upload-summary]');
    const feedback = tradeForm.querySelector('[data-trade-feedback]');
    const progressPanel = tradeForm.querySelector('[data-upload-progress]');
    const progress = tradeForm.querySelector('progress');
    const progressText = tradeForm.querySelector('[data-progress-text]');
    const submit = tradeForm.querySelector('button');
    const maxSize = 4 * 1024 * 1024;
    let selected = [];
    let urls = [];
    let busy = false;

    const message = (text, error = false) => {
        feedback.textContent = text;
        feedback.classList.remove('hidden');
        feedback.classList.toggle('text-rose-300', error);
        feedback.setAttribute('role', error ? 'alert' : 'status');
    };
    const render = () => {
        urls.forEach(url => URL.revokeObjectURL(url));
        urls = [];
        previews.replaceChildren();
        const transfer = new DataTransfer();
        selected.forEach((file, index) => {
            transfer.items.add(file);
            const card = document.createElement('div');
            card.className = 'panel p-2 min-w-0';
            const image = document.createElement('img');
            const url = URL.createObjectURL(file);
            urls.push(url);
            image.src = url;
            image.alt = `Screenshot ${index + 1}`;
            image.className = 'aspect-square w-full rounded-lg object-contain';
            const label = document.createElement('p');
            label.className = 'muted truncate text-xs';
            label.textContent = `${file.name} · ${(file.size / 1024 / 1024).toFixed(2)} MB`;
            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'min-h-11 text-sm text-rose-300';
            remove.textContent = 'Hapus';
            remove.setAttribute('aria-label', `Hapus ${file.name}`);
            remove.addEventListener('click', () => {
                if (busy) return;
                selected.splice(index, 1);
                render();
            });
            card.append(image, label, remove);
            previews.append(card);
        });
        input.files = transfer.files;
        summary.textContent = `${selected.length}/5 gambar · Total ${(selected.reduce((total, file) => total + file.size, 0) / 1024 / 1024).toFixed(2)} MB`;
    };
    input.addEventListener('change', () => {
        const errors = [];
        for (const file of input.files) {
            if (selected.some(item => item.name === file.name && item.size === file.size && item.lastModified === file.lastModified)) continue;
            if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
                errors.push(`${file.name}: gunakan JPG, PNG, atau WebP.`);
            } else if (!file.size || file.size > maxSize) {
                errors.push(`${file.name}: ukuran gambar harus lebih dari 0 dan maksimal 4 MB. Perkecil gambar sebelum dipilih.`);
            } else if (selected.length >= 5) {
                errors.push('Maksimal 5 screenshot. Hapus salah satu gambar untuk menggantinya.');
            } else {
                selected.push(file);
            }
        }
        render();
        if (errors.length) message([...new Set(errors)].join('\n'), true);
        else feedback.classList.add('hidden');
    });
    tradeForm.addEventListener('submit', event => {
        event.preventDefault();
        if (busy || !tradeForm.reportValidity()) return;
        if (!selected.length || selected.length > 5 || selected.some(file => file.size > maxSize)) {
            message('Pilih 1–5 screenshot, masing-masing maksimal 4 MB.', true);
            return;
        }
        const payload = new FormData(tradeForm);
        const controls = [...tradeForm.querySelectorAll('input, select, textarea, button')];
        const disabledStates = controls.map(control => control.disabled);
        busy = true;
        controls.forEach(control => { control.disabled = true; });
        submit.textContent = 'Mengirim…';
        feedback.classList.add('hidden');
        progressPanel.classList.remove('hidden');
        progress.value = 0;
        const xhr = new XMLHttpRequest();
        xhr.open('POST', tradeForm.action);
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.timeout = 180000;
        xhr.upload.onprogress = event => {
            if (event.lengthComputable) {
                progress.value = Math.round(event.loaded / event.total * 100);
                progressText.textContent = progress.value === 100 ? 'Upload selesai, menunggu penyimpanan server…' : `${progress.value}% terkirim`;
            }
        };
        xhr.onload = () => {
            let response = {};
            try { response = JSON.parse(xhr.responseText); } catch { /* A proxy may return HTML for 413/502 responses. */ }
            if (xhr.status === 201) {
                tradeForm.reset();
                selected = [];
                render();
                message(response.message);
            } else {
                const errors = response.errors ? Object.values(response.errors).flat().join('\n') : null;
                const fallback = xhr.status === 413 ? 'Ukuran upload melampaui batas server. Kurangi ukuran/jumlah gambar, lalu kirim ulang.'
                    : xhr.status === 419 ? 'Sesi telah kedaluwarsa. Salin isian penting terlebih dahulu, lalu muat ulang halaman dan pilih kembali screenshot.'
                    : xhr.status === 429 ? 'Terlalu banyak percobaan. Tunggu satu menit sebelum mengirim ulang.'
                    : 'Server belum dapat memproses pengajuan. Data form tetap tersimpan di halaman ini. Coba lagi nanti.';
                message(errors || (xhr.status === 413 ? response.message || fallback : fallback), true);
            }
            feedback.focus();
        };
        xhr.onerror = xhr.ontimeout = () => message('Koneksi terputus atau waktu kirim habis. Data tetap ada di form. Hubungi admin untuk memastikan pengajuan sudah diterima sebelum mengirim ulang.', true);
        xhr.onloadend = () => {
            busy = false;
            controls.forEach((control, index) => { control.disabled = disabledStates[index]; });
            submit.textContent = 'Kirim pengajuan ↗';
            progressPanel.classList.add('hidden');
        };
        xhr.send(payload);
    });
}
