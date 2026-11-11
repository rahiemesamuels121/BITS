import test from 'node:test';
import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { Window } from 'happy-dom';

test('client reference settings use stable keys and keep the selected question optional', async () => {
    const html = execFileSync(process.env.PHP_BINARY || 'php', ['tests/Support/render-form-builder.php', '--existing'], {encoding: 'utf8'});
    const window = new Window({url: 'http://localhost:8000/dashboard/forms/1/edit', settings: {
        enableJavaScriptEvaluation: false, disableJavaScriptFileLoading: true, enableCSSFileLoading: false, enableImageFileLoading: false,
    }});
    window.document.write(html);
    const previousFetch = globalThis.fetch;
    globalThis.window = window; globalThis.document = window.document;
    try {
        await import('../../resources/js/form-builder.js?client-reference');
        const root = document.querySelector('[data-form-builder]');
        const settings = document.querySelector('#fb-settings');
        root.querySelector('[data-builder-settings-open]').click();
        const reference = settings.querySelector('[data-setting="client_reference_field"]');
        assert.deepEqual([...reference.options].map(option => option.value), ['', 'q_service']);
        assert.match(reference.options[1].textContent, /Service \(q_service\)/);
        settings.querySelector('[data-fb-close]').click();
        root.querySelector('[data-field-key="q_service"] [data-field-action="edit"]').click();
        const getToggle = text => [...root.querySelectorAll('[data-property-content] label')].find(label => label.textContent.includes(text)).querySelector('input');
        getToggle('Required question').click();
        assert.equal(getToggle('Required question').checked, true);
        getToggle('Link responses to the selected client').click();
        assert.equal(getToggle('Required question').checked, false);
        const label = root.querySelector('[data-property-content] input[type="text"]');
        label.value = 'Preferred client'; label.dispatchEvent(new window.Event('input', {bubbles: true}));
        root.querySelector('[data-builder-settings-open]').click();
        assert.equal(reference.value, 'q_service');
        assert.match(reference.selectedOptions[0].textContent, /Preferred client \(q_service\)/);
        settings.querySelector('[data-fb-close]').click();
        let payload; let revision = 1;
        globalThis.fetch = async (_url, request) => {
            payload = JSON.parse(request.body);
            return new Response(JSON.stringify({revision: ++revision, status: 'draft'}), {status: 200});
        };
        root.querySelector('[data-builder-save]').click();
        await new Promise(setImmediate);
        assert.equal(payload.settings.client_reference_field, 'q_service');
        assert.equal(payload.fields.find(field => field.field_key === 'q_service').required, false);
        assert.equal(payload.settings.allow_anonymous, true);
        assert.equal(payload.settings.require_authentication, false);
        root.querySelector('[data-builder-settings-open]').click();
        reference.value = ''; reference.dispatchEvent(new window.Event('input', {bubbles: true}));
        settings.querySelector('[data-fb-close]').click();
        root.querySelector('[data-builder-save]').click();
        await new Promise(setImmediate);
        assert.equal(payload.settings.client_reference_field, null);
    } finally {
        globalThis.fetch = previousFetch;
        delete globalThis.window; delete globalThis.document;
        await window.happyDOM.close();
    }
});
