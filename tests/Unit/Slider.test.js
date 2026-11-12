import assert from 'node:assert/strict';
import test from 'node:test';
import { wrapIndex } from '../../resources/js/slider.js';

test('wrapIndex moves forward and wraps to the first slide', () => {
    assert.equal(wrapIndex(0, 1, 3), 1);
    assert.equal(wrapIndex(2, 1, 3), 0);
});

test('wrapIndex moves backward and wraps to the last slide', () => {
    assert.equal(wrapIndex(0, -1, 3), 2);
    assert.equal(wrapIndex(1, -1, 3), 0);
});

test('wrapIndex keeps a direct jump in range and survives an empty slider', () => {
    assert.equal(wrapIndex(4, 0, 3), 1);
    assert.equal(wrapIndex(0, 1, 0), 0);
});
