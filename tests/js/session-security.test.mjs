import test from 'node:test';
import assert from 'node:assert/strict';

test('history restoration revalidates private pages without reloading public pages or normal navigation', async () => {
    const originalWindow = globalThis.window;
    const originalDocument = globalThis.document;
    let listener;
    let privatePage = true;
    let reloads = 0;
    globalThis.window = {
        addEventListener: (name, callback) => { assert.equal(name, 'pageshow'); listener = callback; },
        location: { reload: () => { reloads++; } },
    };
    globalThis.document = { querySelector: () => privatePage ? {} : null };
    try {
        await import('../../resources/js/session-security.js');
        listener({ persisted: false });
        assert.equal(reloads, 0);
        listener({ persisted: true });
        assert.equal(reloads, 1);
        privatePage = false;
        listener({ persisted: true });
        assert.equal(reloads, 1);
    } finally {
        globalThis.window = originalWindow;
        globalThis.document = originalDocument;
    }
});
