document.querySelector('[data-menu-toggle]')?.addEventListener('click', (event) => {
    const menu = document.querySelector('[data-mobile-menu]');
    const open = menu.classList.toggle('hidden') === false;
    event.currentTarget.setAttribute('aria-expanded', String(open));
});

document.querySelectorAll('[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (!window.confirm(form.dataset.confirm)) {
            event.preventDefault();
        }
    });
});

document.querySelectorAll('[data-submit-on-change]').forEach((control) => {
    control.addEventListener('change', (event) => {
        event.currentTarget.form?.requestSubmit();
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
