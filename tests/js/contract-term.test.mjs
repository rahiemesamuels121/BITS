import test from 'node:test';
import assert from 'node:assert/strict';
import { Window } from 'happy-dom';

test('indefinite contracts hide dates and fixed contracts require an ordered date range', async () => {
    const window = new Window();
    const previous = globalThis.document;
    globalThis.document = window.document;
    try {
        document.body.innerHTML = `<div data-contract-term><select data-contract-duration><option value="1">Indefinite</option><option value="0">Dated</option></select><div data-contract-dates><input type="date" name="starts_at" value="2026-11-11"><input type="date" name="ends_at" value="2027-01-01"></div></div>`;
        await import('../../resources/js/contract-term.js');
        const select = document.querySelector('select');
        const dates = document.querySelector('[data-contract-dates]');
        const start = document.querySelector('[name="starts_at"]');
        const end = document.querySelector('[name="ends_at"]');
        assert.equal(dates.hidden, true);
        assert.equal(start.disabled, true);
        assert.equal(end.required, false);
        select.value = '0';
        select.dispatchEvent(new window.Event('change'));
        assert.equal(dates.hidden, false);
        assert.equal(start.disabled, false);
        assert.equal(start.required, true);
        assert.equal(end.required, true);
        assert.equal(end.min, '2026-11-11');
        start.value = '2026-12-01';
        start.dispatchEvent(new window.Event('input'));
        assert.equal(end.min, '2026-12-01');
        select.value = '1';
        select.dispatchEvent(new window.Event('change'));
        assert.equal(end.disabled, true);
        assert.equal(end.required, false);
        assert.equal(end.value, '2027-01-01');
    } finally {
        globalThis.document = previous;
        await window.happyDOM.close();
    }
});
