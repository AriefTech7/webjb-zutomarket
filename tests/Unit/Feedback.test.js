import assert from 'node:assert/strict';
import test from 'node:test';
import { buildFeedbackMailto } from '../../resources/js/feedback.js';

test('buildFeedbackMailto addresses the store email with the type in the subject', () => {
    const url = buildFeedbackMailto('zutomarketcs@gmail.com', { type: 'Saran', name: 'Budi', message: 'Tambah game Valorant & PUBG' });

    assert.ok(url.startsWith('mailto:zutomarketcs@gmail.com?subject='));
    const params = new URLSearchParams(url.split('?')[1]);
    assert.equal(params.get('subject'), '[Saran] Kritik & saran ZutoMarket');
    assert.equal(params.get('body'), 'Jenis: Saran\nNama: Budi\n\nTambah game Valorant & PUBG');
    assert.ok(!url.includes('+'));
});

test('buildFeedbackMailto leaves out the name line when no name is given', () => {
    const params = new URLSearchParams(buildFeedbackMailto('cs@example.com', { type: 'Kritik', name: '', message: 'Lambat' }).split('?')[1]);

    assert.equal(params.get('body'), 'Jenis: Kritik\n\nLambat');
});
