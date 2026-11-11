import test from 'node:test';
import assert from 'node:assert/strict';

// No browser is needed to exercise the renderer's pure condition evaluator.
globalThis.document = { querySelectorAll: () => [] };
const { fieldVisible } = await import('../../resources/js/form-renderer.js');
const conditional = (operator, value) => ({ conditional_rules: { field: 'source', operator, value } });

test('hidden ancestors cannot reveal a dependent question', () => {
    assert.equal(fieldVisible(conditional('not_equals', 'No'), { source: 'Yes' }, { source: false }), false);
    assert.equal(fieldVisible(conditional('not_equals', 'No'), { source: '' }, { source: true }), false);
});

test('boolean false and numeric zero are valid condition answers', () => {
    assert.equal(fieldVisible(conditional('equals', false), { source: '0' }, { source: true }), true);
    assert.equal(fieldVisible(conditional('equals', '0.0'), { source: 0 }, { source: true }), true);
    assert.equal(fieldVisible(conditional('equals', '1.0'), { source: 1 }, { source: true }), true);
});

test('contains handles exact checkbox options and text substrings', () => {
    assert.equal(fieldVisible(conditional('contains', 'Tomatoes'), { source: ['Tomatoes', 'Carrots'] }, { source: true }), true);
    assert.equal(fieldVisible(conditional('contains', 'Tomato'), { source: ['Tomatoes'] }, { source: true }), false);
    assert.equal(fieldVisible(conditional('contains', 'farm'), { source: 'small farm' }, { source: true }), true);
});
