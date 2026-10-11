(() => {
    'use strict';

    const storageKey = 'schoolshare.theme';
    const choices = ['light', 'dark', 'system'];
    const root = document.documentElement;
    const systemTheme = window.matchMedia?.('(prefers-color-scheme: dark)');
    let preference = 'system';

    try {
        const saved = window.localStorage.getItem(storageKey);
        if (choices.includes(saved)) {
            preference = saved;
        }
    } catch {
        // A blocked storage API must not prevent the page from rendering.
    }

    const apply = () => {
        const theme = preference === 'system'
            ? (systemTheme ? (systemTheme.matches ? 'dark' : 'light') : 'dark')
            : preference;

        root.setAttribute('data-bs-theme', theme);
        root.setAttribute('data-theme-preference', preference);
        root.setAttribute('data-theme-ready', '');
        root.style.colorScheme = theme;
        document.querySelectorAll('[data-theme-control]').forEach((control) => {
            control.value = preference;
            control.disabled = false;
        });
    };

    apply();

    document.addEventListener('DOMContentLoaded', () => {
        apply();
        document.querySelectorAll('[data-theme-control]').forEach((control) => {
            control.addEventListener('change', () => {
                if (!choices.includes(control.value)) {
                    return;
                }
                preference = control.value;
                try {
                    window.localStorage.setItem(storageKey, preference);
                } catch {
                    // Keep the chosen theme for this page when storage is blocked.
                }
                apply();
            });
        });
    });

    systemTheme?.addEventListener('change', () => {
        if (preference === 'system') {
            apply();
        }
    });

    window.addEventListener('storage', (event) => {
        if (event.key === storageKey || event.key === null) {
            preference = choices.includes(event.newValue) ? event.newValue : 'system';
            apply();
        }
    });
})();
