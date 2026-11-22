import assert from 'node:assert/strict';
import test from 'node:test';
import { evidenceError, MAX_EVIDENCE } from '../../resources/js/report.js';

const file = (size) => ({ size });

test('evidence needs 1 to 10 images of at most 10 MB', () => {
    assert.equal(evidenceError([]), 'Upload minimal 1 gambar bukti.');
    assert.equal(evidenceError([file(1000)]), '');
    assert.equal(evidenceError(Array.from({ length: MAX_EVIDENCE }, () => file(1000))), '');
    assert.equal(evidenceError(Array.from({ length: MAX_EVIDENCE + 1 }, () => file(1000))), 'Maksimal 10 gambar bukti.');
    assert.equal(evidenceError([file(11 * 1024 * 1024)]), 'Setiap gambar bukti maksimal 10 MB.');
});
