import assert from 'node:assert/strict';
import test from 'node:test';
import { buildOrderMessage, countHeroes, formatRupiah } from '../../resources/js/joki.js';

const order = { rank: 'Epic', price: 6000, stars: 10, loginVia: 'Moonton', userId: '123 Budi', loginId: 'budi@example.com', heroes: 'Layla, Miya, Tigreal', notes: '' };

test('formatRupiah uses Indonesian thousand separators', () => {
    assert.equal(formatRupiah(6000), 'Rp 6.000');
    assert.equal(formatRupiah(1500000), 'Rp 1.500.000');
});

test('countHeroes counts names separated by commas or new lines and ignores blanks', () => {
    assert.equal(countHeroes('Layla, Miya'), 2);
    assert.equal(countHeroes('Layla,\nMiya, , Tigreal,'), 3);
    assert.equal(countHeroes('   '), 0);
});

test('buildOrderMessage includes the order details and calculated total', () => {
    const message = buildOrderMessage(order);

    assert.match(message, /Rank saat ini: Epic \(Rp 6\.000\/bintang\)/);
    assert.match(message, /Jumlah bintang: 10/);
    assert.match(message, /Total: Rp 60\.000/);
    assert.match(message, /Request hero: Layla, Miya, Tigreal/);
});

test('buildOrderMessage uses the package price instead of stars when a package is chosen', () => {
    const message = buildOrderMessage({ ...order, packageName: 'Legend V – Mythic Immortal', packagePrice: 1150000 });

    assert.match(message, /Paket: Legend V – Mythic Immortal/);
    assert.match(message, /Total: Rp 1\.150\.000/);
    assert.ok(!message.includes('Jumlah bintang'));
    assert.ok(!message.includes('/bintang'));
});

test('buildOrderMessage never contains a password value', () => {
    const message = buildOrderMessage({ ...order, password: 'rahasia-sekali' });

    assert.ok(!message.includes('rahasia-sekali'));
    assert.match(message, /Password: \(tempel di sini\)/);
});

test('buildOrderMessage only adds notes when they are filled in', () => {
    assert.ok(!buildOrderMessage(order).includes('Catatan:'));
    assert.match(buildOrderMessage({ ...order, notes: 'Pakai worker A' }), /Catatan: Pakai worker A/);
});
