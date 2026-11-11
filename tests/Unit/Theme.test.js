import assert from 'node:assert/strict';
import test from 'node:test';
import { nextTheme, resolveTheme } from '../../resources/js/theme.js';

test('resolveTheme keeps the dark design unless light was chosen', () => {
    assert.equal(resolveTheme(null), 'dark');
    assert.equal(resolveTheme('dark'), 'dark');
    assert.equal(resolveTheme('light'), 'light');
    assert.equal(resolveTheme('rusak'), 'dark');
});

test('nextTheme switches between light and dark', () => {
    assert.equal(nextTheme('dark'), 'light');
    assert.equal(nextTheme('light'), 'dark');
    assert.equal(nextTheme(undefined), 'light');
});
