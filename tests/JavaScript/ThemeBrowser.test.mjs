import assert from 'node:assert/strict';
import { spawn, spawnSync } from 'node:child_process';
import { once } from 'node:events';
import { mkdtempSync, readFileSync, readdirSync, writeFileSync } from 'node:fs';
import { createServer } from 'node:http';
import { tmpdir } from 'node:os';
import { join, resolve } from 'node:path';
import test from 'node:test';

// Opt-in visual regression check using installed Chromium; no additional dependencies.
// Fixtures come from real Laravel HTTP tests with an in-memory database and fake storage.
test('rendered pages remain readable in both themes on desktop and mobile', {
    skip: process.env.THEME_BROWSER !== '1', timeout: 240_000,
}, async () => {
    const directory = mkdtempSync(join(tmpdir(), 'schoolshare-theme-browser-'));
    const rendered = spawnSync('php', ['vendor/bin/phpunit', 'tests/Feature/ThemeRenderingTest.php'], {
        cwd: resolve('.'), encoding: 'utf8',
        env: {
            ...process.env, THEME_BROWSER_FIXTURES: directory,
            APP_ENV: 'testing', APP_CONFIG_CACHE: join(directory, 'config.php'),
            DB_CONNECTION: 'sqlite', DB_DATABASE: ':memory:', DB_URL: '',
            MAIL_MAILER: 'array', CACHE_STORE: 'array', SESSION_DRIVER: 'array',
            ASSET_URL: 'http://localhost',
        },
    });
    assert.equal(rendered.status, 0, rendered.stdout + rendered.stderr);
    const pages = readdirSync(directory).filter((name) => name.endsWith('.html'));
    assert.ok(pages.length >= 30, 'Expected public, guest, app, and admin fixtures');
    const server = createServer((request, response) => {
        const pathname = new URL(request.url, 'http://localhost').pathname;
        if (request.method !== 'GET') { response.writeHead(405).end(); return; }
        let path;
        if (pathname === '/theme.js' || pathname === '/theme.css') {
            path = resolve('public', pathname.slice(1));
        } else if (pages.includes(pathname.slice(1))) {
            path = join(directory, pathname.slice(1));
        } else { response.writeHead(404).end(); return; }
        response.setHeader('Content-Type', path.endsWith('.js') ? 'text/javascript' : path.endsWith('.css') ? 'text/css' : 'text/html');
        response.end(readFileSync(path));
    });
    server.listen(0, '127.0.0.1');
    await once(server, 'listening');
    const origin = `http://127.0.0.1:${server.address().port}`;
    const browser = spawn(process.env.CHROMIUM_BIN ?? 'chromium', [
        '--headless', '--disable-gpu', '--no-first-run', '--no-default-browser-check',
        '--remote-debugging-port=0', `--user-data-dir=${join(directory, 'profile')}`, 'about:blank',
    ], { stdio: ['ignore', 'ignore', 'pipe'] });
    let socket;
    const failures = [];
    const reports = [];
    const exceptions = [];
    try {
        const endpoint = await new Promise((accept, reject) => {
            let output = '';
            const timer = setTimeout(() => reject(new Error(`Chromium startup timed out: ${output}`)), 15_000);
            browser.on('error', reject);
            browser.on('exit', (code) => { clearTimeout(timer); reject(new Error(`Chromium exited ${code}: ${output}`)); });
            browser.stderr.on('data', (data) => {
                output += data;
                const match = output.match(/DevTools listening on (ws:\/\/[^\s]+)/);
                if (match) { clearTimeout(timer); accept(match[1]); }
            });
        });
        const debuggerOrigin = endpoint.replace(/^ws:/, 'http:').split('/devtools')[0];
        const targets = await (await fetch(`${debuggerOrigin}/json/list`)).json();
        socket = new WebSocket(targets.find((target) => target.type === 'page').webSocketDebuggerUrl);
        await once(socket, 'open');
        let sequence = 0;
        const pending = new Map();
        socket.addEventListener('message', ({ data }) => {
            const message = JSON.parse(data);
            if (message.method === 'Runtime.exceptionThrown') exceptions.push(message.params.exceptionDetails.text);
            const request = pending.get(message.id);
            if (!request) return;
            pending.delete(message.id);
            clearTimeout(request.timer);
            if (message.error) request.reject(new Error(JSON.stringify(message.error)));
            else request.accept(message.result);
        });
        const send = (method, params = {}) => new Promise((accept, reject) => {
            const id = ++sequence;
            const timer = setTimeout(() => { pending.delete(id); reject(new Error(`CDP timed out: ${method}`)); }, 15_000);
            pending.set(id, { accept, reject, timer });
            socket.send(JSON.stringify({ id, method, params }));
        });
        const evaluate = async (expression) => {
            const result = await send('Runtime.evaluate', { expression, returnByValue: true, awaitPromise: true });
            assert.equal(result.exceptionDetails, undefined, JSON.stringify(result.exceptionDetails));
            return result.result.value;
        };
        await send('Page.enable');
        await send('Runtime.enable');
        await send('Emulation.setFocusEmulationEnabled', { enabled: true });
        const navigate = async (name, scriptsEnabled = true) => {
            await send('Page.navigate', { url: `${origin}/${name}` });
            for (let attempt = 0; attempt < 150; attempt++) {
                if (await evaluate(`document.readyState === 'complete' && location.pathname === ${JSON.stringify('/' + name)}`)) break;
                await new Promise((accept) => setTimeout(accept, 100));
                assert.ok(attempt < 149, `Loading timed out: ${name}`);
            }
            if (scriptsEnabled) {
                await evaluate('document.fonts.ready.then(() => true)');
                await evaluate(`Promise.race([
                    Promise.allSettled(document.getAnimations().filter(a => a.effect.getTiming().iterations !== Infinity).map(a => a.finished)),
                    new Promise(r => setTimeout(r, 2000))
                ]).then(() => new Promise(r => requestAnimationFrame(() => requestAnimationFrame(r))))`);
            }
        };
        const audit = async () => evaluate(`(${auditPage.toString()})()`);
        await navigate('home.html');
        assert.equal(await evaluate(`(() => { const e = document.createElement('div'); e.className = 'd-none'; document.body.append(e); const value = getComputedStyle(e).display; e.remove(); return value; })()`), 'none', 'Bootstrap CDN must load for meaningful browser checks');
        for (const width of [1440, 390]) {
            await send('Emulation.setDeviceMetricsOverride', { width, height: width === 390 ? 844 : 900, deviceScaleFactor: 1, mobile: width === 390 });
            for (const theme of ['light', 'dark']) {
                await evaluate(`localStorage.setItem('schoolshare.theme', '${theme}')`);
                for (const page of pages) {
                    await navigate(page);
                    assert.equal(await evaluate('document.documentElement.dataset.bsTheme'), theme, page);
                    const result = await audit();
                    reports.push({ page, width, theme, ...result });
                    if (result.failures.length || result.overflow || !result.controlVisible) failures.push({ page, width, theme, ...result });
                    if (['explore-empty.html', 'explore-populated.html', 'home.html', 'profile-populated.html'].includes(page)) {
                        const shot = await send('Page.captureScreenshot', { format: 'png', captureBeyondViewport: page !== 'home.html' });
                        writeFileSync(join(directory, `${page.replace('.html', '')}-${theme}-${width}.png`), Buffer.from(shot.data, 'base64'));
                    }
                }
                // Open actual Bootstrap components without submitting forms or mutating data.
                await navigate('dashboard-populated.html');
                await evaluate(`document.querySelector('[data-bs-toggle="dropdown"]').click()`);
                assert.equal(await evaluate(`!!document.querySelector('.dropdown-menu.show')`), true, 'Account dropdown must open');
                const dropdown = await audit();
                if (dropdown.failures.length) failures.push({ page: 'dropdown', width, theme, ...dropdown });
                const point = await evaluate(`(() => { const e = document.querySelector('.dropdown-menu.show .dropdown-item'); const r = e.getBoundingClientRect(); return { x: r.x + r.width / 2, y: r.y + r.height / 2 }; })()`);
                await send('Input.dispatchMouseEvent', { type: 'mouseMoved', ...point });
                await evaluate('new Promise(r => requestAnimationFrame(() => requestAnimationFrame(r)))');
                const hover = await audit();
                if (hover.failures.length) failures.push({ page: 'dropdown-hover', width, theme, ...hover });
                if (width === 390) {
                    await evaluate(`document.querySelector('[data-bs-target="#ssSidebar"]').click()`);
                    await new Promise((accept) => setTimeout(accept, 400));
                    assert.equal(await evaluate(`!!document.querySelector('#ssSidebar.show')`), true, 'Mobile navigation must open');
                    const drawer = await audit();
                    if (drawer.failures.length) failures.push({ page: 'mobile-sidebar', width, theme, ...drawer });
                }
                await navigate('admin-upgrades.html');
                await evaluate(`document.querySelector('[data-bs-toggle="modal"]').click()`);
                await new Promise((accept) => setTimeout(accept, 400));
                assert.equal(await evaluate(`!!document.querySelector('.modal.show')`), true, 'Admin modal must open');
                const modal = await audit();
                if (modal.failures.length) failures.push({ page: 'admin-modal', width, theme, ...modal });
                const modalShot = await send('Page.captureScreenshot', { format: 'png' });
                writeFileSync(join(directory, `admin-modal-${theme}-${width}.png`), Buffer.from(modalShot.data, 'base64'));
                await navigate('projects-create-populated.html');
                const focus = await evaluate(`(async () => { const e = [...document.querySelectorAll('input:not([type="hidden"])')].find(e => e.getClientRects().length); e.focus(); e.classList.add('is-invalid'); await new Promise(r => requestAnimationFrame(() => requestAnimationFrame(r))); const s = getComputedStyle(e); return { active: document.activeElement === e, focused: e.matches(':focus'), documentFocused: document.hasFocus(), class: e.className, shadow: s.boxShadow, outline: s.outline, border: s.borderColor }; })()`);
                if (!focus.active || focus.shadow === 'none') failures.push({ page: 'input-focus', width, theme, focus });
                await send('Input.dispatchKeyEvent', { type: 'keyDown', key: 'Tab', code: 'Tab', windowsVirtualKeyCode: 9 });
                await send('Input.dispatchKeyEvent', { type: 'keyUp', key: 'Tab', code: 'Tab', windowsVirtualKeyCode: 9 });
                assert.equal(await evaluate(`document.activeElement !== document.body`), true);
            }
        }
        // Exercise the real control, persistence, system media changes, and no-JS fallback.
        await navigate('login.html');
        await evaluate(`(() => { const e = document.querySelector('[data-theme-control]'); e.value = 'light'; e.dispatchEvent(new Event('change')); })()`);
        await navigate('explore-populated.html');
        assert.equal(await evaluate('document.documentElement.dataset.bsTheme'), 'light');
        await evaluate(`(() => { const e = document.querySelector('[data-theme-control]'); e.value = 'system'; e.dispatchEvent(new Event('change')); })()`);
        for (const theme of ['dark', 'light']) {
            await send('Emulation.setEmulatedMedia', { features: [{ name: 'prefers-color-scheme', value: theme }] });
            await evaluate('new Promise(r => requestAnimationFrame(() => requestAnimationFrame(r)))');
            assert.equal(await evaluate('document.documentElement.dataset.bsTheme'), theme);
        }
        const blockedStorage = await send('Page.addScriptToEvaluateOnNewDocument', {
            source: `Object.defineProperty(window, 'localStorage', { get() { throw new Error('Storage blocked by browser check'); } });`,
        });
        await navigate('login.html');
        assert.equal(await evaluate('document.documentElement.dataset.bsTheme'), 'light');
        await evaluate(`(() => { const e = document.querySelector('[data-theme-control]'); e.value = 'dark'; e.dispatchEvent(new Event('change')); })()`);
        assert.equal(await evaluate('document.documentElement.dataset.bsTheme'), 'dark');
        await send('Page.removeScriptToEvaluateOnNewDocument', { identifier: blockedStorage.identifier });
        await send('Emulation.setScriptExecutionDisabled', { value: true });
        await navigate('explore-populated.html', false);
        assert.equal(await evaluate('document.documentElement.dataset.bsTheme'), 'dark');
        assert.equal(await evaluate(`getComputedStyle(document.querySelector('.ss-theme-switcher')).display`), 'none');
        await send('Emulation.setScriptExecutionDisabled', { value: false });
        assert.deepEqual(exceptions, [], 'Unexpected browser JavaScript exceptions');
        assert.deepEqual(failures, [], `Contrast/layout failures; inspect ${directory}/report.json`);
    } finally {
        writeFileSync(join(directory, 'report.json'), JSON.stringify({ reports, failures, exceptions }, null, 2));
        process.stdout.write(`Browser report: ${directory}/report.json; ${reports.length} page/theme/viewport checks\n`);
        socket?.close();
        browser.kill();
        server.close();
    }
});

function auditPage() {
    const parse = (value) => value.match(/[\d.]+/g)?.map(Number) ?? [0, 0, 0, 0];
    const blend = (foreground, background) => {
        const alpha = foreground[3] ?? 1;
        return foreground.slice(0, 3).map((value, index) => value * alpha + background[index] * (1 - alpha));
    };
    const luminance = (color) => color.map((value) => {
        const linear = value / 255;
        return linear <= 0.04045 ? linear / 12.92 : ((linear + 0.055) / 1.055) ** 2.4;
    }).reduce((sum, value, index) => sum + value * [0.2126, 0.7152, 0.0722][index], 0);
    const visible = (element) => {
        if (!element.getClientRects().length || element.closest('[disabled], .visually-hidden, [aria-hidden="true"]')) return false;
        let current = element;
        while (current) {
            const style = getComputedStyle(current);
            if (style.visibility === 'hidden' || style.display === 'none' || Number(style.opacity) === 0) return false;
            current = current.parentElement;
        }
        const rect = element.getBoundingClientRect();
        return rect.right > 0 && rect.left < innerWidth;
    };
    const failures = [];
    let checked = 0;
    const check = (element, text) => {
        if (!visible(element) || element.tagName === 'OPTION') return;
        const style = getComputedStyle(element);
        const ancestors = [];
        for (let current = element; current; current = current.parentElement) ancestors.unshift(current);
        let background = [255, 255, 255];
        for (const ancestor of ancestors) background = blend(parse(getComputedStyle(ancestor).backgroundColor), background);
        const foreground = blend(parse(style.color), background);
        const bright = luminance(foreground), dark = luminance(background);
        const ratio = (Math.max(bright, dark) + 0.05) / (Math.min(bright, dark) + 0.05);
        const large = parseFloat(style.fontSize) >= 24 || (parseFloat(style.fontSize) >= 18.66 && Number(style.fontWeight) >= 700);
        const target = large ? 3 : 4.5;
        checked++;
        if (ratio + 0.01 < target) failures.push({ text: text.trim().slice(0, 90), element: element.tagName + '.' + element.className, ratio: Number(ratio.toFixed(2)), target, color: style.color, background });
    };
    const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
    while (walker.nextNode()) {
        const node = walker.currentNode;
        if (!node.textContent.trim() || ['SCRIPT', 'STYLE', 'NOSCRIPT'].includes(node.parentElement.tagName)) continue;
        check(node.parentElement, node.textContent);
    }
    document.querySelectorAll('input:not([type="hidden"]):not([type="checkbox"]):not([type="radio"]), textarea, select').forEach((element) => check(element, element.placeholder || element.value || element.tagName));
    const control = document.querySelector('[data-theme-control]');
    const rect = control.getBoundingClientRect();
    return { checked, failures, overflow: document.documentElement.scrollWidth > innerWidth + 1, controlVisible: visible(control) && rect.right <= innerWidth && rect.left >= 0 };
}
