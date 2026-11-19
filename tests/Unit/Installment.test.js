import assert from 'node:assert/strict';
import test from 'node:test';
import { dueDaysFor, formatRupiah, simulate } from '../../resources/js/installment.js';

const tiers = [{ max: 100000, days: 7 }, { max: 300000, days: 16 }, { max: 500000, days: 23 }, { max: null, days: 31 }];

test('payoff days follow the price tier, with the open-ended tier last', () => {
    assert.equal(dueDaysFor(tiers, 100000), 7);
    assert.equal(dueDaysFor(tiers, 100001), 16);
    assert.equal(dueDaysFor(tiers, 450000), 23);
    assert.equal(dueDaysFor(tiers, 5000000), 31);
    assert.equal(dueDaysFor([{ max: 100000, days: 7 }], 200000), null);
});

test('simulate splits the price into DP and balance', () => {
    assert.deepEqual(simulate(450000, 30, tiers), { total: 450000, dp: 135000, remaining: 315000, days: 23 });
    assert.deepEqual(simulate(450000, null, tiers), { total: 450000, dp: 0, remaining: 450000, days: 23 });
    assert.equal(formatRupiah(135000), 'Rp 135.000');
});
