import { initialiseCountdowns } from './countdown';
import { initialiseInstallPrompt } from './install';
import { initialiseThemeToggle } from './theme';
import { initialiseJokiOrder } from './joki';
import { initialiseMoneyInputs } from './money';
import { initialiseProfile, PROFILE_KEY } from './profile';
import { initialiseSliders } from './slider';
import { initialiseWishlist, WISHLIST_KEY } from './wishlist';

initialiseWishlist();
initialiseProfile();
initialiseJokiOrder();
initialiseCountdowns();
initialiseInstallPrompt();
initialiseThemeToggle();
initialiseMoneyInputs();
initialiseSliders();

document.querySelectorAll('[data-password-toggle]').forEach((button) => {
    const input = document.getElementById(button.dataset.passwordToggle);
    button.addEventListener('click', () => {
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        button.setAttribute('aria-pressed', String(show));
        button.setAttribute('aria-label', show ? 'Sembunyikan password' : 'Tampilkan password');
    });
});

// Signing out on a shared phone must not leave the account's wishlist or profile behind in this browser.
document.querySelectorAll('[data-account-logout]').forEach((form) => form.addEventListener('submit', () => {
    try {
        localStorage.removeItem(PROFILE_KEY);
        localStorage.removeItem(WISHLIST_KEY);
    } catch {
        // Storage blocked: nothing was stored either.
    }
}));

document.querySelectorAll('[data-chip-row]').forEach((row) => {
    const active = row.querySelector('[aria-current="page"]');
    if (active && row.scrollWidth > row.clientWidth) {
        row.scrollLeft = active.offsetLeft - (row.clientWidth - active.offsetWidth) / 2;
    }
});

document.querySelectorAll('[data-tabs]').forEach((list) => {
    const tabs = [...list.querySelectorAll('[role="tab"]')];
    tabs.forEach((tab) => tab.addEventListener('click', () => {
        tabs.forEach((other) => {
            const selected = other === tab;
            other.setAttribute('aria-selected', String(selected));
            document.getElementById(other.getAttribute('aria-controls')).hidden = !selected;
        });
    }));
});

document.querySelectorAll('[data-carousel]').forEach((carousel) => {
    const track = carousel.querySelector('[data-carousel-track]');
    const prev = carousel.querySelector('[data-carousel-prev]');
    const next = carousel.querySelector('[data-carousel-next]');
    const step = () => Math.max(track.clientWidth * 0.8, 160);
    // Arrows only make sense when the posters overflow the strip.
    const toggleArrows = () => {
        const scrollable = track.scrollWidth > track.clientWidth + 1;
        [prev, next].forEach((button) => button?.classList.toggle('md:flex', scrollable));
    };
    prev?.addEventListener('click', () => track.scrollBy({ left: -step(), behavior: 'smooth' }));
    next?.addEventListener('click', () => track.scrollBy({ left: step(), behavior: 'smooth' }));
    window.addEventListener('resize', toggleArrows);
    track.querySelectorAll('img').forEach((image) => image.addEventListener('load', toggleArrows));
    toggleArrows();
});

document.querySelectorAll('[data-bulk-delete]').forEach((form) => {
    const items = [...document.querySelectorAll(`[data-bulk-item][form="${form.id}"]`)];
    const selectAll = form.querySelector('[data-bulk-all]');
    const submit = form.querySelector('[data-bulk-submit]');
    // The table (desktop) and the list (phone) both carry a checkbox per account, so they are kept in sync by id.
    const selectedIds = () => new Set(items.filter((item) => item.checked).map((item) => item.value));
    const refresh = () => {
        const count = selectedIds().size;
        const total = new Set(items.map((item) => item.value)).size;
        form.querySelector('[data-bulk-count]').textContent = count;
        submit.disabled = count === 0;
        selectAll.checked = count > 0 && count === total;
        selectAll.indeterminate = count > 0 && count < total;
    };
    items.forEach((item) => item.addEventListener('change', () => {
        items.filter((other) => other.value === item.value).forEach((other) => { other.checked = item.checked; });
        refresh();
    }));
    selectAll.addEventListener('change', () => {
        items.forEach((item) => { item.checked = selectAll.checked; });
        refresh();
    });
    form.addEventListener('submit', (event) => {
        const count = selectedIds().size;
        if (count === 0 || !window.confirm(`Hapus ${count} akun terpilih beserta gambarnya? Tindakan ini tidak bisa dibatalkan.`)) {
            event.preventDefault();
        }
    });
    refresh();
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
