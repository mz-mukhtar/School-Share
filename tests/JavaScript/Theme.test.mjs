import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';
import test from 'node:test';

const source = readFileSync(new URL('../../public/theme.js', import.meta.url), 'utf8');

function setup({ saved = null, dark = false, blocked = false, mediaSupported = true } = {}) {
    const attributes = new Map();
    const listeners = {};
    const storage = new Map(saved === null ? [] : [['schoolshare.theme', saved]]);
    const control = { value: '', disabled: true, addEventListener: (name, callback) => { listeners[name] = callback; } };
    const root = { style: {}, setAttribute: (name, value) => attributes.set(name, value) };
    const media = { matches: dark, addEventListener: (_, callback) => { listeners.media = callback; } };
    const window = {
        localStorage: {
            getItem: (key) => { if (blocked) throw new Error('Blocked'); return storage.get(key) ?? null; },
            setItem: (key, value) => { if (blocked) throw new Error('Blocked'); storage.set(key, value); },
        },
        addEventListener: (name, callback) => { listeners[name] = callback; },
        ...(mediaSupported ? { matchMedia: () => media } : {}),
    };
    const document = {
        documentElement: root,
        querySelectorAll: () => [control],
        addEventListener: (name, callback) => { listeners[name] = callback; },
    };
    runInNewContext(source, { window, document });
    listeners.DOMContentLoaded();
    return { attributes, control, listeners, media, storage, root };
}

test('defaults to the system theme and enables the accessible control', () => {
    const page = setup();
    assert.equal(page.attributes.get('data-bs-theme'), 'light');
    assert.equal(page.attributes.get('data-theme-preference'), 'system');
    assert.equal(page.control.value, 'system');
    assert.equal(page.control.disabled, false);
    page.media.matches = true;
    page.listeners.media();
    assert.equal(page.attributes.get('data-bs-theme'), 'dark');
});

for (const theme of ['light', 'dark']) {
    test(`restores and persists an explicit ${theme} preference independently of the OS`, () => {
        const page = setup({ saved: theme, dark: theme === 'light' });
        assert.equal(page.attributes.get('data-bs-theme'), theme);
        page.media.matches = !page.media.matches;
        page.listeners.media();
        assert.equal(page.attributes.get('data-bs-theme'), theme);
        page.control.value = theme === 'light' ? 'dark' : 'light';
        page.listeners.change();
        assert.equal(page.storage.get('schoolshare.theme'), page.control.value);
        assert.equal(page.root.style.colorScheme, page.control.value);
    });
}

test('system selection follows subsequent OS changes', () => {
    const page = setup({ saved: 'dark' });
    page.control.value = 'system';
    page.listeners.change();
    assert.equal(page.storage.get('schoolshare.theme'), 'system');
    assert.equal(page.attributes.get('data-bs-theme'), 'light');
});

test('blocked storage still renders and accepts changes for the current page', () => {
    const page = setup({ blocked: true, dark: true });
    assert.equal(page.attributes.get('data-bs-theme'), 'dark');
    page.control.value = 'light';
    page.listeners.change();
    assert.equal(page.attributes.get('data-bs-theme'), 'light');
});

test('ignores invalid stored and submitted preferences', () => {
    const page = setup({ saved: 'invalid' });
    assert.equal(page.attributes.get('data-theme-preference'), 'system');
    page.control.value = 'invalid';
    page.listeners.change();
    assert.equal(page.attributes.get('data-bs-theme'), 'light');
    assert.equal(page.storage.get('schoolshare.theme'), 'invalid');
});

test('synchronizes other tabs and resets to system when storage is cleared', () => {
    const page = setup();
    page.listeners.storage({ key: 'unrelated', newValue: 'dark' });
    assert.equal(page.attributes.get('data-bs-theme'), 'light');
    page.listeners.storage({ key: 'schoolshare.theme', newValue: 'dark' });
    assert.equal(page.control.value, 'dark');
    page.listeners.storage({ key: null, newValue: null });
    assert.equal(page.control.value, 'system');
    page.listeners.storage({ key: 'schoolshare.theme', newValue: 'invalid' });
    assert.equal(page.attributes.get('data-theme-preference'), 'system');
});

test('uses a readable dark fallback if media queries are unavailable', () => {
    const page = setup({ mediaSupported: false });
    assert.equal(page.attributes.get('data-bs-theme'), 'dark');
});
