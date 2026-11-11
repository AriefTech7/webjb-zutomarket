export const THEME_KEY = 'zutomarket-theme';
export const THEME_COLORS = { dark: '#070b1c', light: '#f3f5fb' };

/** The site is designed dark, so dark stays the default until a visitor picks light. */
export function resolveTheme(stored) {
    return stored === 'light' ? 'light' : 'dark';
}

export function nextTheme(current) {
    return current === 'light' ? 'dark' : 'light';
}

export function applyTheme(theme, root = document.documentElement) {
    root.dataset.theme = theme;
    document.querySelector('meta[name="theme-color"]')?.setAttribute('content', THEME_COLORS[theme]);
    document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
        button.setAttribute('aria-label', theme === 'light' ? 'Ganti ke mode gelap' : 'Ganti ke mode terang');
        button.setAttribute('aria-pressed', String(theme === 'light'));
    });
}

export function initialiseThemeToggle() {
    applyTheme(resolveTheme(document.documentElement.dataset.theme));
    document.querySelectorAll('[data-theme-toggle]').forEach((button) => button.addEventListener('click', () => {
        const theme = nextTheme(document.documentElement.dataset.theme);
        applyTheme(theme);
        try {
            window.localStorage.setItem(THEME_KEY, theme);
        } catch {
            // Without storage the choice simply lasts until the page is closed.
        }
    }));
}
