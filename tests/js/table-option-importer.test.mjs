import test from 'node:test';
import assert from 'node:assert/strict';
import { Window } from 'happy-dom';
import { tableOptionImporter } from '../../resources/js/table-option-importer.js';

test('table importer sends filters, previews safely, applies options and invalidates stale results', async () => {
    const window = new Window();
    const originalDocument = globalThis.document;
    const originalFetch = globalThis.fetch;
    globalThis.document = window.document;
    let payload;
    let resolve;
    let applied; let appliedLabels; let appliedLive;
    globalThis.fetch = async (url, request) => {
        payload = JSON.parse(request.body);
        return new Promise(done => { resolve = done; });
    };
    try {
        const box = tableOptionImporter(
            {roles: {label: 'Roles', columns: {name: 'Name', code: 'Code'}}},
            '/options',
            (options, labels) => { applied = options; appliedLabels = labels; },
            configuration => { appliedLive = configuration; },
        );
        document.body.append(box);
        const button = text => [...box.querySelectorAll('button')].find(node => node.textContent === text);
        button('+ Add filter').click();
        box.querySelector('[data-filter-column]').value = 'code';
        box.querySelector('[data-filter-value]').value = 'agency';
        button('Preview options').click();
        assert.deepEqual(payload, {source: 'roles', column: 'name', filters: [{column: 'code', operator: 'equals', value: 'agency'}]});
        resolve({ok: true, json: async () => ({options: ['1', '2'], option_labels: {'1': 'Agency', '2': '<script>unsafe</script>'}})});
        await new Promise(done => setImmediate(done));
        assert.equal(box.querySelector('script'), null);
        assert.equal(box.querySelectorAll('li').length, 2);
        button('Use these options').click();
        assert.deepEqual(applied, ['1', '2']);
        assert.deepEqual(appliedLabels, {'1': 'Agency', '2': '<script>unsafe</script>'});
        button('Use live database options').click();
        assert.deepEqual(appliedLive, {source: 'roles', column: 'name', filters: [{column: 'code', operator: 'equals', value: 'agency'}]});
        assert.equal(box.querySelector('li').textContent, 'Agency (ID: 1)');
        box.querySelector('[data-filter-value]').dispatchEvent(new window.Event('input', {bubbles: true}));
        assert.equal(button('Use these options').disabled, true);
        button('Preview options').click();
        box.querySelector('[data-filter-value]').dispatchEvent(new window.Event('input', {bubbles: true}));
        resolve({ok: true, json: async () => ({options: ['Stale result']})});
        await new Promise(done => setImmediate(done));
        assert.equal(button('Use these options').disabled, true);
        assert.equal(box.querySelectorAll('li').length, 0);
    } finally {
        globalThis.document = originalDocument;
        globalThis.fetch = originalFetch;
        await window.happyDOM.close();
    }
});

test('live preview displays labels while preserving IDs and default selection', async () => {
    const window = new Window();
    const originalDocument = globalThis.document;
    globalThis.document = window.document;
    try {
        const { buildPreview } = await import('../../resources/js/form-renderer.js');
        const preview = buildPreview({title: 'Choose agency', fields: [{
            field_key: 'agency', label: 'Agency', type: 'select', options: ['12', '34'],
            option_labels: {'12': 'Travel agency', '34': 'Travel agency'}, default_value: '34', required: true,
        }]}, {});
        document.body.append(preview);
        const select = preview.querySelector('select');
        assert.equal(select.value, '34');
        assert.deepEqual([...select.options].slice(1).map(option => [option.value, option.textContent]), [
            ['12', 'Travel agency'], ['34', 'Travel agency'],
        ]);
    } finally {
        globalThis.document = originalDocument;
        await window.happyDOM.close();
    }
});

test('live filters reopen with named role/status values and retain all rules when applied', async () => {
    const window = new Window();
    const originalDocument = globalThis.document;
    globalThis.document = window.document;
    try {
        const source = {source: 'users', column: 'name', filters: [
            {column: 'role_id', operator: 'equals', value: '3'},
            {column: 'is_active', operator: 'equals', value: '1'},
        ]};
        let applied;
        const box = tableOptionImporter({users: {label: 'Accounts / Clients', columns: {name: 'Name'}, filter_columns: {
            name: {label: 'Name', type: 'text'},
            role_id: {label: 'Role', type: 'select', choices: {'1': 'Agency', '2': 'Admin', '3': 'Staff'}},
            is_active: {label: 'Account status', type: 'select', choices: {'1': 'Active', '0': 'Inactive'}},
        }}}, '/options', () => {}, configuration => { applied = configuration; }, source);
        document.body.append(box);
        const buttons = text => [...box.querySelectorAll('button')].find(button => button.textContent === text);
        const values = [...box.querySelectorAll('[data-filter-value]')];
        assert.equal(box.open, true);
        assert.equal(values[0].tagName, 'SELECT');
        assert.equal(values[0].selectedOptions[0].textContent, 'Staff');
        assert.equal(values[1].selectedOptions[0].textContent, 'Active');
        buttons('Use live database options').click();
        assert.deepEqual(applied, source);
        values[1].value = '0';
        values[1].dispatchEvent(new window.Event('change', {bubbles: true}));
        buttons('Use live database options').click();
        assert.equal(applied.filters[1].value, '0');
        buttons('+ Add filter').click();
        applied = undefined;
        buttons('Use live database options').click();
        assert.equal(applied, undefined, 'incomplete filters must not be applied');
        assert.match(box.querySelector('[role="status"]').textContent, /Complete each filter/);
        const textFilter = box.querySelectorAll('[data-filter-value]')[2];
        textFilter.value = 'travel';
        const match = box.querySelectorAll('[data-filter-operator]')[2]; match.value = 'contains';
        buttons('Use live database options').click();
        assert.equal(applied.filters.length, 3);
        assert.deepEqual(applied.filters[2], {column: 'name', operator: 'contains', value: 'travel'});
        buttons('+ Add filter').click(); buttons('+ Add filter').click();
        assert.equal(buttons('+ Add filter').disabled, true);
        buttons('Remove filter').click();
        assert.equal(buttons('+ Add filter').disabled, false);
    } finally {
        globalThis.document = originalDocument;
        await window.happyDOM.close();
    }
});

test('changing filter columns uses date controls and changing tables clears incompatible filters', async () => {
    const window = new Window();
    const originalDocument = globalThis.document;
    globalThis.document = window.document;
    try {
        let applied;
        const box = tableOptionImporter({
            contracts: {label: 'Contracts', columns: {name: 'Name'}, filter_columns: {name: {label: 'Name', type: 'text'}, ends_at: {label: 'End date', type: 'date'}}},
            roles: {label: 'Roles', columns: {name: 'Role name'}},
        }, '/options', () => {}, configuration => { applied = configuration; });
        document.body.append(box);
        const button = text => [...box.querySelectorAll('button')].find(node => node.textContent === text);
        button('+ Add filter').click();
        const field = box.querySelector('[data-filter-column]'); field.value = 'ends_at';
        field.dispatchEvent(new window.Event('change', {bubbles: true}));
        const value = box.querySelector('[data-filter-value]');
        assert.equal(value.type, 'date');
        assert.equal(box.querySelector('[data-filter-operator] option[value="contains"]'), null);
        value.value = '2026-11-11';
        box.querySelector('[data-filter-operator]').value = 'greater_or_equal';
        button('Use live database options').click();
        assert.deepEqual(applied.filters, [{column: 'ends_at', operator: 'greater_or_equal', value: '2026-11-11'}]);
        const sourceSelect = box.querySelector('select'); sourceSelect.value = 'roles';
        sourceSelect.dispatchEvent(new window.Event('change'));
        assert.equal(box.querySelectorAll('[data-filter-column]').length, 0);
        assert.match(box.querySelector('.fb-source-filter-summary').textContent, /No filters/);
        button('Use live database options').click();
        assert.deepEqual(applied, {source: 'roles', column: 'name', filters: []});
    } finally {
        globalThis.document = originalDocument;
        await window.happyDOM.close();
    }
});
