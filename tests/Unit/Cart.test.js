import test from 'node:test';
import assert from 'node:assert/strict';
import { CART_KEY, CART_LIMIT, createCartStore, fetchCart } from '../../resources/js/cart.js';

function memoryStorage(initial = null) {
    const data = new Map(initial === null ? [] : [[CART_KEY, initial]]);
    return {
        getItem: (key) => data.get(key) ?? null,
        setItem: (key, value) => data.set(key, value),
    };
}

test('cart keeps ids in the order they were added and toggles them off again', () => {
    const storage = memoryStorage();
    const store = createCartStore(storage);
    assert.deepEqual(store.toggle(5), [5]);
    assert.deepEqual(store.toggle(9), [5, 9]);
    assert.deepEqual(createCartStore(storage).toggle(5), [9]);
    assert.equal(storage.getItem(CART_KEY), '[9]');
});

test('corrupted or unsafe stored values are dropped', () => {
    assert.deepEqual(createCartStore(memoryStorage('{bad')).ids(), []);
    assert.deepEqual(createCartStore(memoryStorage('[1,1,"2",0,-3,2.5,4]')).ids(), [1, 4]);
});

test('cart refuses more than the limit', () => {
    const store = createCartStore(memoryStorage(JSON.stringify(Array.from({ length: CART_LIMIT }, (_, index) => index + 1))));
    assert.throws(() => store.toggle(CART_LIMIT + 1), RangeError);
    assert.deepEqual(store.toggle(1).length, CART_LIMIT - 1);
});

test('fetchCart sends the ids and rejects ids the server did not ask about', async () => {
    let requested;
    const fetcher = async (url) => {
        requested = url;
        return { ok: true, json: async () => ({ ids: [2], html: '<li>2</li>' }) };
    };
    const result = await fetchCart([2, 3], '/keranjang/accounts', fetcher);
    assert.deepEqual(result.ids, [2]);
    assert.deepEqual(requested.searchParams.getAll('ids[]'), ['2', '3']);

    const rogue = async () => ({ ok: true, json: async () => ({ ids: [7], html: '' }) });
    await assert.rejects(fetchCart([2], '/keranjang/accounts', rogue));
    await assert.rejects(fetchCart([2], '/keranjang/accounts', async () => ({ ok: false })));
});
