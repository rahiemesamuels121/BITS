import test from 'node:test';
import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { Window } from 'happy-dom';

test('saved questions remain editable and option blur does not discard the next click', async () => {
    const html = execFileSync(process.env.PHP_BINARY || 'php', ['tests/Support/render-form-builder.php', '--existing'], { encoding: 'utf8' });
    const originalFetch = globalThis.fetch;
    for (const width of [1440, 900]) {
        const window = new Window({ url: 'http://localhost:8000/dashboard/forms/1/edit', width,
            settings: { enableJavaScriptEvaluation: false, disableJavaScriptFileLoading: true, enableCSSFileLoading: false, enableImageFileLoading: false } });
        window.document.write(html);
        globalThis.window = window;
        globalThis.document = window.document;
        const errors = [];
        window.addEventListener('error', event => errors.push(event.error || event.message));
        try {
            await import(`../../resources/js/form-builder.js?editing=${width}`);
            const root = document.querySelector('[data-form-builder]');
            const edit = key => root.querySelector(`[data-field-key="${key}"] [data-field-action="edit"]`).click();
            const type = (input, value) => {
                input.focus(); input.value = value;
                input.dispatchEvent(new window.Event('input', { bubbles: true }));
            };
            edit('q_name');
            const label = root.querySelector('[data-property-content] input');
            assert.equal(document.activeElement, label);
            type(label, 'Updated name');
            assert.equal(root.querySelector('[data-field-key="q_name"] h3').textContent, 'Updated name');
            if (width === 900) assert.equal(root.querySelector('[data-builder-active-panel]').dataset.builderActivePanel, 'properties');
            root.querySelector('[data-builder-panel="canvas"]').click();
            edit('q_service');
            const option = root.querySelector('[aria-label="Answer option 1"]');
            const addOption = [...root.querySelectorAll('[data-property-content] button')].find(button => button.textContent.includes('Add option'));
            type(option, 'Agency team');
            // A real click commits the active text input before dispatching click.
            option.dispatchEvent(new window.Event('change', { bubbles: true }));
            option.blur();
            assert.equal(addOption.isConnected, true, 'Committing an option must not replace the button being clicked.');
            assert.equal(root.querySelector('[data-default-options]').value, 'Agency team');
            addOption.click();
            assert.equal(root.querySelectorAll('.fb-option-editor').length, 3);
            root.querySelector('[data-builder-panel="toolbox"]').click();
            root.querySelector('[data-field-type="email"]').click();
            if (width === 900) assert.equal(root.querySelector('[data-builder-active-panel]').dataset.builderActivePanel, 'properties', 'Adding a question opens its editor on smaller screens.');
            type(root.querySelector('[data-property-content] input'), 'Contact email');
            let payload;
            globalThis.fetch = async (_url, options) => {
                payload = JSON.parse(options.body);
                return new Response(JSON.stringify({ revision: 2, status: 'draft' }), { status: 200 });
            };
            root.querySelector('[data-builder-save]').click();
            await new Promise(setImmediate);
            assert.equal(payload.fields[0].label, 'Updated name');
            assert.deepEqual(payload.fields[1].options, ['Agency team', 'Admin', 'Option 3']);
            assert.equal(payload.fields[2].label, 'Contact email');
            assert.equal(payload.revision, 1);
            assert.deepEqual(errors, []);
        } finally {
            await window.happyDOM.close();
            delete globalThis.window;
            delete globalThis.document;
            globalThis.fetch = originalFetch;
        }
    }
});
