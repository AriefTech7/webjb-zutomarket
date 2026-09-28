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

document.querySelectorAll('[data-gallery-image]').forEach((button) => {
    button.addEventListener('click', () => {
        document.querySelector('[data-main-image]').src = button.dataset.galleryImage;
        document.querySelectorAll('[data-gallery-image]').forEach((item) => item.setAttribute('aria-pressed', String(item === button)));
    });
});
