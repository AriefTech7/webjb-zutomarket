import assert from 'node:assert/strict';
import test from 'node:test';
import { lastMessageId, onlyNew } from '../../resources/js/chat.js';

test('lastMessageId keeps the highest id seen', () => {
    assert.equal(lastMessageId([], 7), 7);
    assert.equal(lastMessageId([{ id: 3 }, { id: 12 }, { id: 9 }], 5), 12);
    assert.equal(lastMessageId([{ id: 2 }], 10), 10);
});

test('onlyNew drops messages already on screen', () => {
    assert.deepEqual(onlyNew([{ id: 1 }, { id: 2 }, { id: 3 }], new Set([1, 3])), [{ id: 2 }]);
});
