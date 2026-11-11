import test from 'node:test';
import assert from 'node:assert/strict';
import { Window } from 'happy-dom';

test('role and search filters retain selections and show an empty state', async () => {
    const window = new Window();
    globalThis.document = window.document;
    try {
        document.body.innerHTML = `<fieldset data-contract-assignments>
            <div data-assignment-filters hidden><input data-assignment-search><select data-assignment-role><option value="">All</option><option value="agency">Agency</option><option value="admin">Admin</option></select></div>
            <table><tbody>
                <tr data-assignment-row data-role="agency" data-search="alice alice@example.com"><td><input type="checkbox" name="client_ids[]" value="1" checked></td></tr>
                <tr data-assignment-row data-role="agency" data-search="bob bob@example.com"><td><input type="checkbox" name="client_ids[]" value="2"></td></tr>
                <tr data-assignment-row data-role="admin" data-search="administrator"><td><input type="checkbox" name="client_ids[]" value="3" disabled></td></tr>
                <tr data-assignment-empty hidden><td>No accounts</td></tr>
            </tbody></table><p data-assignment-summary></p></fieldset>`;
        await import('../../resources/js/contract-assignments.js');
        const rows = [...document.querySelectorAll('[data-assignment-row]')];
        const role = document.querySelector('select');
        const search = document.querySelector('[data-assignment-search]');
        const summary = document.querySelector('[data-assignment-summary]');
        role.value = 'admin';
        role.dispatchEvent(new window.Event('change', { bubbles: true }));
        assert.deepEqual(rows.map(row => row.hidden), [true, true, false]);
        assert.equal(rows[0].querySelector('input').checked, true);
        assert.equal(summary.textContent, '1 selected · 1 of 3 accounts shown');
        search.value = 'alice';
        search.dispatchEvent(new window.Event('input'));
        assert.equal(document.querySelector('[data-assignment-empty]').hidden, false);
        role.value = 'agency';
        role.dispatchEvent(new window.Event('change', { bubbles: true }));
        assert.deepEqual(rows.map(row => row.hidden), [false, true, true]);
        search.value = '';
        search.dispatchEvent(new window.Event('input'));
        rows[1].querySelector('input').checked = true;
        rows[1].querySelector('input').dispatchEvent(new window.Event('change', { bubbles: true }));
        assert.equal(summary.textContent, '2 selected · 2 of 3 accounts shown');
    } finally {
        delete globalThis.document;
        await window.happyDOM.close();
    }
});

test('assignment durations enable only selected clients and preserve custom dates when filtering', async () => {
    const window = new Window();
    const originalDocument = globalThis.document;
    globalThis.document = window.document;
    try {
        const controls = (id, checked = '') => `<input type="checkbox" name="client_ids[]" value="${id}" ${checked}>
            <select name="client_assignments[${id}][duration]" data-assignment-duration><option value="contract">Use contract duration</option><option value="custom" selected>Custom dates</option></select>
            <div data-assignment-custom-dates><input type="date" name="client_assignments[${id}][starts_at]" value="2026-11-01" data-assignment-start><input type="date" name="client_assignments[${id}][ends_at]" value="2027-02-01" data-assignment-end></div>
            <small data-assignment-inherited>Use contract duration</small>`;
        document.body.innerHTML = `<form><fieldset data-contract-assignments>
            <div data-assignment-filters hidden><input data-assignment-search><select data-assignment-role><option value="">All</option><option value="agency">Agency</option><option value="admin">Admin</option></select></div>
            <table><tbody><tr data-assignment-row data-role="agency" data-search="alice"><td>${controls('1', 'checked')}</td></tr>
            <tr data-assignment-row data-role="agency" data-search="bob"><td>${controls('2')}</td></tr>
            <tr data-assignment-empty hidden><td>No matches</td></tr></tbody></table><p data-assignment-summary></p>
            </fieldset></form>`;
        const { initializeContractAssignments } = await import('../../resources/js/contract-assignments.js');
        initializeContractAssignments(document.querySelector('[data-contract-assignments]'));
        const rows = [...document.querySelectorAll('[data-assignment-row]')];
        const first = rows[0];
        const checkbox = first.querySelector('[name="client_ids[]"]');
        const duration = first.querySelector('[data-assignment-duration]');
        const start = first.querySelector('[data-assignment-start]');
        const end = first.querySelector('[data-assignment-end]');
        const dates = first.querySelector('[data-assignment-custom-dates]');
        const role = document.querySelector('[data-assignment-role]');
        const change = control => control.dispatchEvent(new window.Event('change', { bubbles: true }));
        assert.equal(duration.disabled, false);
        assert.equal(start.required, true);
        assert.equal(end.min, '2026-11-01');
        assert.equal(rows[1].querySelector('[data-assignment-duration]').disabled, true);
        assert.equal(rows[1].querySelector('[data-assignment-start]').required, false);
        role.value = 'admin'; change(role);
        assert.equal(first.hidden, true);
        assert.equal(start.disabled, false, 'filtering must not disable selected assignment dates');
        assert.equal(new window.FormData(document.querySelector('form')).get('client_assignments[1][ends_at]'), '2027-02-01');
        assert.equal(rows[1].querySelector('[data-assignment-start]').disabled, true);
        const search = document.querySelector('[data-assignment-search]');
        search.value = 'not a matching account';
        search.dispatchEvent(new window.Event('input'));
        start.value = '';
        assert.equal(start.reportValidity(), false);
        assert.equal(role.value, '', 'invalid dates clear role filtering so the browser can focus the field');
        assert.equal(search.value, '', 'invalid dates clear search filtering so the browser can focus the field');
        assert.equal(first.hidden, false);
        assert.equal(checkbox.checked, true);
        assert.equal(start.required, true);
        assert.equal(start.value, '', 'revealing invalid dates must not fill or change user input');
        assert.equal(end.value, '2027-02-01');
        start.value = '2026-11-01';
        duration.value = 'contract'; change(duration);
        assert.equal(dates.hidden, true);
        assert.equal(start.disabled, true);
        assert.equal(end.required, false);
        assert.equal(first.querySelector('[data-assignment-inherited]').hidden, false);
        duration.value = 'custom'; change(duration);
        assert.equal(dates.hidden, false);
        assert.equal(start.value, '2026-11-01');
        start.value = '2026-12-01';
        start.dispatchEvent(new window.Event('input', {bubbles: true}));
        assert.equal(end.min, '2026-12-01');
        checkbox.checked = false; change(checkbox);
        assert.equal(duration.disabled, true);
        assert.equal(start.disabled, true);
        assert.equal(start.required, false);
        assert.equal(end.value, '2027-02-01', 'deselecting must preserve date choices if reselected');
        checkbox.checked = true; change(checkbox);
        assert.equal(duration.disabled, false);
        assert.equal(start.required, true);
        assert.equal(end.value, '2027-02-01');
    } finally {
        globalThis.document = originalDocument;
        await window.happyDOM.close();
    }
});
