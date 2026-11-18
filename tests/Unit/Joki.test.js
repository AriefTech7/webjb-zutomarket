import assert from 'node:assert/strict';
import test from 'node:test';
import { buildOrderMessage, countHeroes, describeRank, formatRupiah, ladderPosition, localPhone, quote, rankOptions, tierAt } from '../../resources/js/joki.js';

const tiers = [
    { name: 'Epic', price: 6000, divisions: 5 },
    { name: 'Legend', price: 7000, divisions: 5 },
    { name: 'Mythic', price: 8000, from: 0, to: 24 },
    { name: 'Mythical Honor', price: 9000, from: 25, to: 49 },
    { name: 'Mythical Glory', price: 11000, from: 50, to: 99 },
    { name: 'Mythical Immortal', price: 15000, from: 100, to: 500 },
];
const ranks = rankOptions(tiers);
const rank = (id) => ranks.find((option) => option.id === id);

test('formatRupiah uses Indonesian thousand separators', () => {
    assert.equal(formatRupiah(6000), 'Rp 6.000');
    assert.equal(formatRupiah(1500000), 'Rp 1.500.000');
});

test('countHeroes counts names separated by commas or new lines and ignores blanks', () => {
    assert.equal(countHeroes('Layla,\nMiya, , Tigreal,'), 3);
    assert.equal(countHeroes('   '), 0);
});

test('rank options list Epic V..I, Legend V..I, then the Mythic tiers', () => {
    assert.deepEqual(ranks.map((option) => option.id), [
        'Epic V', 'Epic IV', 'Epic III', 'Epic II', 'Epic I', 'Legend V', 'Legend IV', 'Legend III', 'Legend II', 'Legend I',
        'Mythic', 'Mythical Honor', 'Mythical Glory', 'Mythical Immortal',
    ]);
    assert.deepEqual([rank('Epic I').min, rank('Epic I').max], [0, 4]);
    assert.deepEqual([rank('Mythical Glory').min, rank('Mythical Glory').max], [50, 99]);
});

test('the ladder is continuous across divisions and into Mythic', () => {
    assert.equal(ladderPosition(rank('Epic V'), 4) + 1, ladderPosition(rank('Epic IV'), 0));
    assert.equal(ladderPosition(rank('Legend I'), 4) + 1, ladderPosition(rank('Mythic'), 0));
    assert.equal(ladderPosition(rank('Mythic'), 24) + 1, ladderPosition(rank('Mythical Honor'), 25));
    assert.equal(tierAt(tiers, ladderPosition(rank('Legend I'), 4)).name, 'Legend');
    assert.equal(tierAt(tiers, ladderPosition(rank('Mythical Honor'), 49)).name, 'Mythical Honor');
    assert.equal(tierAt(tiers, ladderPosition(rank('Mythical Immortal'), 300)).name, 'Mythical Immortal');
});

test('quote charges every star at the price of the tier it is climbed in', () => {
    const result = quote(tiers, ladderPosition(rank('Epic I'), 3), ladderPosition(rank('Mythic'), 2));
    assert.equal(result.stars, 29);
    assert.deepEqual(result.lines, [{ name: 'Epic', stars: 2, price: 6000 }, { name: 'Legend', stars: 25, price: 7000 }, { name: 'Mythic', stars: 2, price: 8000 }]);
    assert.equal(result.total, 2 * 6000 + 25 * 7000 + 2 * 8000);
});

test('quote adds the service surcharge and gives nothing when the target is not higher', () => {
    const withExtra = quote(tiers, ladderPosition(rank('Mythic'), 0), ladderPosition(rank('Mythic'), 10), 10);
    assert.deepEqual([withExtra.subtotal, withExtra.extra, withExtra.total], [80000, 8000, 88000]);
    const backwards = quote(tiers, ladderPosition(rank('Legend V'), 0), ladderPosition(rank('Epic I'), 0));
    assert.deepEqual([backwards.stars, backwards.total], [0, 0]);
});

const order = {
    mode: 'calculator',
    service: { label: 'Joki Regular' },
    from: describeRank(rank('Epic V'), 0),
    to: describeRank(rank('Epic IV'), 0),
    quote: quote(tiers, 0, 5),
    packages: [],
    heroes: ['Layla', 'Miya', 'Tigreal'],
    userId: '123456', serverId: '2001', needsLogin: true, loginVia: 'Moonton', loginId: 'budi@example.com', notes: '',
    contact: { name: 'Budi', phone: '+628123456789', email: '' },
    password: 'rahasia-sekali',
};

test('buildOrderMessage lists the climb, price breakdown, account and contact', () => {
    const message = buildOrderMessage(order);
    assert.match(message, /Layanan: Joki Regular/);
    assert.match(message, /Rank: Epic V bintang 0 → Epic IV bintang 0 \(5 bintang\)/);
    assert.match(message, /- Epic: 5 × Rp 6\.000/);
    assert.match(message, /Total: Rp 30\.000/);
    assert.match(message, /Request hero: Layla, Miya, Tigreal/);
    assert.match(message, /ID: 123456 \(2001\)/);
    assert.match(message, /WhatsApp: \+628123456789/);
    assert.ok(!message.includes('Catatan:'));
});

test('buildOrderMessage for packages sums the chosen packages', () => {
    const message = buildOrderMessage({ ...order, mode: 'package', packages: [{ name: 'Epic V – Legend V', price: 150000 }, { name: 'Honor – Glory', price: 225000 }] });
    assert.match(message, /- Epic V – Legend V \(Rp 150\.000\)/);
    assert.match(message, /Total: Rp 375\.000/);
    assert.ok(!message.includes('Layanan:'));
});

test('buildOrderMessage never contains the password and skips login data for Joki Gendong', () => {
    assert.ok(!buildOrderMessage(order).includes('rahasia-sekali'));
    assert.match(buildOrderMessage(order), /Password: \(tempel di sini\)/);
    const gendong = buildOrderMessage({ ...order, needsLogin: false, heroes: [] });
    assert.ok(!gendong.includes('Password'));
    assert.ok(!gendong.includes('Masuk melalui'));
});

test('localPhone strips the country code, a leading zero and separators', () => {
    assert.equal(localPhone('0812-3456 789'), '8123456789');
    assert.equal(localPhone('+62 812 3456'), '8123456');
    assert.equal(localPhone('62812'), '812');
});
