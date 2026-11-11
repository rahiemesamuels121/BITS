import test from 'node:test';
import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { Window } from 'happy-dom';

test('the actual Blade builder can add and configure a question', async () => {
    const html = execFileSync(process.env.PHP_BINARY || 'php', ['tests/Support/render-form-builder.php'], {
        encoding: 'utf8', maxBuffer: 4 * 1024 * 1024,
    });
    const window = new Window({
        url: 'http://localhost:8000/dashboard/forms/1/edit', width: 1440,
        settings: { enableJavaScriptEvaluation: false, disableJavaScriptFileLoading: true, enableCSSFileLoading: false, enableImageFileLoading: false },
    });
    window.document.write(html);
    globalThis.window = window;
    globalThis.document = window.document;
    globalThis.Element = window.Element;
    globalThis.ResizeObserver = window.ResizeObserver;
    globalThis.IntersectionObserver = window.IntersectionObserver;
    const errors = [];
    const originalFetch = globalThis.fetch;
    window.addEventListener('error', event => errors.push(event.error || event.message));

    try {
        await import('../../resources/js/app.js');
        const root = document.querySelector('[data-form-builder]');
        assert.ok(root, 'The real Blade page must include the builder.');
        assert.equal(root.querySelectorAll('[data-field-key]').length, 0);
        root.querySelector('[data-add-first]').click();
        assert.deepEqual(errors, []);
        assert.equal(root.querySelectorAll('[data-field-key]').length, 1, 'Clicking Add Short Text must insert a visible question.');
        assert.equal(root.querySelector('[data-builder-empty]').hidden, true);
        assert.match(root.querySelector('[data-save-state]').textContent, /Unsaved changes/);
        const label = root.querySelector('[data-property-content] input[type="text"]');
        label.value = 'Full Name';
        label.dispatchEvent(new window.Event('input', { bubbles: true }));
        assert.equal(root.querySelector('[data-field-key] h3').textContent, 'Full Name');
        root.querySelector('[data-field-type="select"]').click();
        assert.deepEqual(errors, []);
        assert.equal(root.querySelectorAll('[data-field-key]').length, 2);
        root.querySelector('[data-builder-add-question]').click();
        assert.deepEqual(errors, []);
        assert.equal(root.querySelectorAll('[data-field-key]').length, 3, 'Add another question must create a question, not just focus the toolbox.');
        for (const button of root.querySelectorAll('[data-field-type]')) {
            const before = root.querySelectorAll('[data-field-key]').length;
            button.click();
            assert.deepEqual(errors, []);
            assert.equal(root.querySelectorAll('[data-field-key]').length, before + 1, `The ${button.dataset.fieldType} toolbox button must add a question.`);
        }
        const questionCount = root.querySelectorAll('[data-field-key]').length;
        root.querySelector('[data-builder-preview]').click();
        assert.deepEqual(errors, []);
        assert.equal(document.querySelector('#fb-preview').open, true);
        assert.equal(document.querySelectorAll('#fb-preview [data-render-field]').length, questionCount);
        document.querySelector('#fb-preview [data-fb-close]').click();
        root.querySelector('[data-builder-settings-open]').click();
        assert.equal(document.querySelector('#fb-settings').open, true);
        document.querySelector('#fb-settings [data-fb-close]').click();
        let saved;
        let completeSave;
        globalThis.fetch = async (_url, options) => {
            assert.equal(options.method, 'PUT');
            saved = JSON.parse(options.body);
            return new Promise(resolve => { completeSave = resolve; });
        };
        root.querySelector('[data-builder-save]').click();
        await new Promise(setImmediate);
        const loading = document.querySelector('.page-loading');
        assert.equal(loading.hidden, false);
        assert.equal(document.querySelector('.admin-layout').inert, true);
        assert.equal(document.body.getAttribute('aria-busy'), 'true');
        completeSave(new Response(JSON.stringify({revision:1,status:'draft'}), {status:200, headers:{'Content-Type':'application/json'}}));
        await new Promise(setImmediate);
        await new Promise(setImmediate);
        assert.deepEqual(errors, []);
        assert.equal(loading.hidden, true);
        assert.equal(document.querySelector('.admin-layout').inert, false);
        assert.equal(document.body.hasAttribute('aria-busy'), false);
        assert.equal(saved.fields.length, questionCount);
        assert.equal(saved.fields[0].label, 'Full Name');
        assert.equal(root.querySelector('[data-save-state]').classList.contains('is-dirty'), false);
    } finally {
        await window.happyDOM.close();
        delete globalThis.window;
        delete globalThis.document;
        delete globalThis.Element;
        delete globalThis.ResizeObserver;
        delete globalThis.IntersectionObserver;
        globalThis.fetch = originalFetch;
    }
});
