import assert from 'node:assert/strict';
import test from 'node:test';
import { formatRemaining } from '../../resources/js/countdown.js';

test('formatRemaining shows hours, minutes and seconds under one day', () => {
    assert.equal(formatRemaining((5 * 3600 + 4 * 60 + 3) * 1000), '05:04:03');
    assert.equal(formatRemaining(999), 'Berakhir');
});

test('formatRemaining adds days from 24 hours upward', () => {
    assert.equal(formatRemaining(86400 * 1000), '1 hari 00:00:00');
    assert.equal(formatRemaining((2 * 86400 + 61) * 1000), '2 hari 00:01:01');
});

test('formatRemaining reports an ended or invalid countdown', () => {
    assert.equal(formatRemaining(0), 'Berakhir');
    assert.equal(formatRemaining(-5000), 'Berakhir');
    assert.equal(formatRemaining(Number.NaN), 'Berakhir');
});
