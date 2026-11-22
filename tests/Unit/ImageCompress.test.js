import assert from 'node:assert/strict';
import test from 'node:test';
import { COMPRESS_ABOVE, compressImage, fitWithin, formatBytes, MAX_SIDE } from '../../resources/js/image-compress.js';

test('fitWithin shrinks the longest side to the limit and never enlarges', () => {
    assert.deepEqual(fitWithin(4000, 3000), [MAX_SIDE, 1920]);
    assert.deepEqual(fitWithin(1080, 9000), [307, MAX_SIDE]);
    assert.deepEqual(fitWithin(1200, 800), [1200, 800]);
});

test('formatBytes uses MB with a comma, else KB', () => {
    assert.equal(formatBytes(18.4 * 1024 * 1024), '18,4 MB');
    assert.equal(formatBytes(820 * 1024), '820 KB');
});

test('small images, GIFs and non-images are left untouched', async () => {
    const small = { type: 'image/jpeg', size: 500_000 };
    const gif = { type: 'image/gif', size: COMPRESS_ABOVE * 4 };
    const pdf = { type: 'application/pdf', size: COMPRESS_ABOVE * 4 };
    assert.equal(await compressImage(small), small);
    assert.equal(await compressImage(gif), gif);
    assert.equal(await compressImage(pdf), pdf);
});
