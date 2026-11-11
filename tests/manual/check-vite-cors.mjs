import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';

// HTTP/DOM tests do not enforce browser module CORS. Check the real dev server.
const assetOrigin = (await readFile(new URL('../../public/hot', import.meta.url), 'utf8')).trim();
assert.ok(['localhost', '127.0.0.1'].includes(new URL(assetOrigin).hostname), 'Use a local Vite server for this check.');
const modules = ['/@vite/client', '/resources/js/app.js', '/resources/js/forms.js', '/resources/js/form-builder.js', '/resources/js/form-renderer.js'];
for (const origin of ['http://localhost:8000', 'http://127.0.0.1:8000']) {
    for (const module of modules) {
        const response = await fetch(new URL(module, assetOrigin), { headers: { Origin: origin } });
        assert.equal(response.status, 200, `${module} must load successfully.`);
        assert.match(response.headers.get('Content-Type') || '', /javascript/, `${module} must be JavaScript.`);
        assert.equal(response.headers.get('Access-Control-Allow-Origin'), origin, `The browser at ${origin} must be allowed to load ${module}.`);
        await response.arrayBuffer();
    }
    console.log(`Browser module CORS passed for ${origin} (${modules.length} modules).`);
}
const rejected = await fetch(new URL('/resources/js/app.js', assetOrigin), { headers: { Origin: 'https://example.invalid' } });
assert.equal(rejected.headers.get('Access-Control-Allow-Origin'), null, 'Unrelated origins must not be allowed.');
await rejected.arrayBuffer();
console.log('Unrelated origins remain blocked.');
