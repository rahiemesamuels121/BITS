import test from 'node:test';
import assert from 'node:assert/strict';
import { Window } from 'happy-dom';

let testCase = 0;

async function withPage(callback) {
    const window = new Window({
        url: 'http://localhost:8000/dashboard',
        settings: { enableJavaScriptEvaluation: false, disableJavaScriptFileLoading: true, enableCSSFileLoading: false, enableImageFileLoading: false },
    });
    const originalWindow = globalThis.window;
    const originalDocument = globalThis.document;

    globalThis.window = window;
    globalThis.document = window.document;

    try {
        await import(`../../resources/js/page-loading.js?test=${testCase++}`);
        await callback(window);
    } finally {
        await window.happyDOM.close();

        if (originalWindow === undefined) delete globalThis.window;
        else globalThis.window = originalWindow;

        if (originalDocument === undefined) delete globalThis.document;
        else globalThis.document = originalDocument;
    }
}

const nextTask = () => new Promise(resolve => setImmediate(resolve));

test('page links show loading feedback including clicks on nested content', async () => {
    await withPage(async window => {
        document.body.innerHTML = '<a href="/clients"><span>Clients</span></a>';
        document.querySelector('span').dispatchEvent(new window.MouseEvent('click', { bubbles: true, cancelable: true, button: 0 }));
        await nextTask();

        assert.equal(document.querySelector('.page-loading').hidden, false);
        assert.equal(document.querySelector('[data-page-loading-message]').textContent, 'Loading page…');
    });
});

test('anchors, downloads, other targets, modified clicks and canceled navigation stay usable', async () => {
    await withPage(async window => {
        for (const attributes of ['href="#details"', 'href="/file" download', 'href="/clients" target="_blank"', 'href="/clients" target="preview"', 'href="mailto:hello@example.com"', 'href="/clients" data-no-loading']) {
            document.body.innerHTML = `<a ${attributes}>Open</a>`;
            document.querySelector('a').dispatchEvent(new window.MouseEvent('click', { bubbles: true, cancelable: true, button: 0 }));
            await nextTask();
            assert.equal(document.querySelector('.page-loading'), null, attributes);
        }

        document.body.innerHTML = '<a href="/clients">Clients</a>';
        const link = document.querySelector('a');
        link.dispatchEvent(new window.MouseEvent('click', { bubbles: true, cancelable: true, button: 0, ctrlKey: true }));
        document.addEventListener('click', event => event.preventDefault());
        link.dispatchEvent(new window.MouseEvent('click', { bubbles: true, cancelable: true, button: 0 }));
        await nextTask();
        assert.equal(document.querySelector('.page-loading'), null);
    });
});

test('valid form submissions show a blocking spinner and restore the page after history navigation', async () => {
    await withPage(async window => {
        const pageContent = document.createElement('main');
        const alreadyInert = document.createElement('aside');
        alreadyInert.inert = true;
        const form = document.createElement('form');
        document.body.append(pageContent, alreadyInert, form);
        form.dataset.loadingMessage = 'Saving changes…';

        form.dispatchEvent(new window.Event('submit', { bubbles: true, cancelable: true }));
        await nextTask();

        const overlay = document.querySelector('.page-loading');
        assert.ok(overlay);
        assert.equal(overlay.hidden, false);
        assert.equal(overlay.querySelector('[data-page-loading-message]').textContent, 'Saving changes…');
        assert.equal(pageContent.inert, true);
        assert.equal(alreadyInert.inert, true);
        assert.equal(document.body.getAttribute('aria-busy'), 'true');

        const pageshow = new window.Event('pageshow');
        Object.defineProperty(pageshow, 'persisted', { value: true });
        window.dispatchEvent(pageshow);

        assert.equal(overlay.hidden, true);
        assert.equal(pageContent.inert, false);
        assert.equal(alreadyInert.inert, true);
        assert.equal(document.body.hasAttribute('aria-busy'), false);
    });
});

test('canceled form submissions do not show the blocking spinner', async () => {
    await withPage(async window => {
        const form = document.createElement('form');
        form.addEventListener('submit', event => event.preventDefault());
        document.body.append(form);

        form.dispatchEvent(new window.Event('submit', { bubbles: true, cancelable: true }));
        await nextTask();

        assert.equal(document.querySelector('.page-loading'), null);
        assert.equal(document.body.hasAttribute('aria-busy'), false);
    });
});

test('dialog and new-tab forms do not block the current page', async () => {
    await withPage(async window => {
        const dialogForm = document.createElement('form');
        dialogForm.method = 'dialog';
        const newTabForm = document.createElement('form');
        newTabForm.target = '_blank';
        document.body.append(dialogForm, newTabForm);

        dialogForm.dispatchEvent(new window.Event('submit', { bubbles: true, cancelable: true }));
        newTabForm.dispatchEvent(new window.Event('submit', { bubbles: true, cancelable: true }));
        await nextTask();

        assert.equal(document.querySelector('.page-loading'), null);
        assert.equal(document.body.hasAttribute('aria-busy'), false);
    });
});
