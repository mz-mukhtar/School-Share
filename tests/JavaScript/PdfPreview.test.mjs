import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import { test } from 'node:test';

const template = await readFile(new URL('../../resources/views/projects/files/show.blade.php', import.meta.url), 'utf8');
const previewScript = template.match(/<script type="module">([\s\S]*?)<\/script>/)[1]
    .replace(/const url = .*?;/, 'const url = downloadUrl;')
    .replace(/\bimport\(/g, 'loadPdfLibrary(');
const runPreview = new (Object.getPrototypeOf(async function () {}).constructor)(
    'document', 'loadPdfLibrary', 'downloadUrl', previewScript,
);
const downloadUrl = 'https://schoolshare.example/projects/notes/files/12/download?version_id=7';
const settle = async () => {
    for (let turn = 0; turn < 5; turn++) {
        await new Promise(resolve => setImmediate(resolve));
    }
};

function viewer() {
    const elements = new Map();
    for (const id of ['pdf-canvas', 'pdf-loading', 'pdf-controls', 'pdf-page-num', 'pdf-page-count', 'pdf-prev', 'pdf-next']) {
        const classes = new Set(['pdf-canvas', 'pdf-controls'].includes(id) ? ['d-none'] : []);
        const listeners = new Map();
        elements.set(id, {
            textContent: '',
            classList: {
                add: name => classes.add(name),
                remove: name => classes.delete(name),
                contains: name => classes.has(name),
            },
            getContext: () => ({}),
            addEventListener: (name, listener) => listeners.set(name, listener),
            click: () => listeners.get('click')(),
        });
    }

    return { getElementById: id => elements.get(id) };
}

function pdfLibrary({ loadingError, renderingError, firstRender } = {}) {
    const renderedPages = [];
    const requests = [];
    const document = {
        numPages: 3,
        getPage: async pageNumber => ({
            getViewport: () => ({ width: 600, height: 800 }),
            render: () => {
                renderedPages.push(pageNumber);
                return {
                    promise: renderingError ? Promise.reject(renderingError)
                        : pageNumber === 1 && firstRender ? firstRender : Promise.resolve(),
                };
            },
        }),
    };
    const library = {
        GlobalWorkerOptions: {},
        getDocument: options => {
            requests.push(options);
            return { promise: loadingError ? Promise.reject(loadingError) : Promise.resolve(document) };
        },
    };

    return { library, requests, renderedPages };
}

function assertFailureVisible(document) {
    assert.equal(document.getElementById('pdf-loading').classList.contains('d-none'), false);
    assert.equal(document.getElementById('pdf-loading').textContent, 'Failed to load PDF. Please download the file to view it.');
    assert.equal(document.getElementById('pdf-canvas').classList.contains('d-none'), true);
    assert.equal(document.getElementById('pdf-controls').classList.contains('d-none'), true);
}

test('loads a matching patched worker, disables evaluation, and renders the requested version', async () => {
    const document = viewer();
    const { library, requests, renderedPages } = pdfLibrary();
    let libraryUrl;

    await runPreview(document, async url => { libraryUrl = url; return library; }, downloadUrl);
    await settle();

    assert.equal(libraryUrl, 'https://cdn.jsdelivr.net/npm/pdfjs-dist@6.4.299/build/pdf.min.mjs');
    assert.equal(library.GlobalWorkerOptions.workerSrc, 'https://cdn.jsdelivr.net/npm/pdfjs-dist@6.4.299/build/pdf.worker.min.mjs');
    assert.deepEqual(requests, [{ url: downloadUrl, isEvalSupported: false }]);
    assert.deepEqual(renderedPages, [1]);
    assert.equal(document.getElementById('pdf-canvas').classList.contains('d-none'), false);
    assert.equal(document.getElementById('pdf-page-count').textContent, 3);
});

test('queues navigation during rendering and respects the first and last page', async () => {
    const document = viewer();
    let finishFirstRender;
    const firstRender = new Promise(resolve => { finishFirstRender = resolve; });
    const { library, renderedPages } = pdfLibrary({ firstRender });

    await runPreview(document, async () => library, downloadUrl);
    document.getElementById('pdf-prev').click();
    document.getElementById('pdf-next').click();
    document.getElementById('pdf-next').click();
    document.getElementById('pdf-next').click();
    assert.deepEqual(renderedPages, [1]);
    finishFirstRender();
    await settle();
    document.getElementById('pdf-prev').click();
    await settle();

    assert.deepEqual(renderedPages, [1, 3, 2]);
    assert.equal(document.getElementById('pdf-page-num').textContent, 2);
});

test('shows a download fallback if the library cannot load', async () => {
    const document = viewer();

    await runPreview(document, async () => { throw new Error('CDN unavailable'); }, downloadUrl);

    assertFailureVisible(document);
});

test('shows a download fallback if the PDF cannot load', async () => {
    const document = viewer();
    const { library } = pdfLibrary({ loadingError: new Error('<img onerror=alert(1)>') });

    await runPreview(document, async () => library, downloadUrl);

    assertFailureVisible(document);
});

test('shows a download fallback if rendering fails', async () => {
    const document = viewer();
    const { library } = pdfLibrary({ renderingError: new Error('Invalid page') });

    await runPreview(document, async () => library, downloadUrl);
    await settle();

    assertFailureVisible(document);
});
