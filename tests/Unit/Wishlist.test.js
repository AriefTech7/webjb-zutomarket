import test from 'node:test';
import assert from 'node:assert/strict';
import { createWishlistStore, fetchWishlist, WISHLIST_KEY } from '../../resources/js/wishlist.js';

function memoryStorage(initial = null) {
    const data = new Map(initial === null ? [] : [[WISHLIST_KEY, initial]]);
    return {
        getItem: (key) => data.get(key) ?? null,
        setItem: (key, value) => data.set(key, value),
    };
}

test('wishlist persists across store instances and toggles without duplicates', () => {
    const storage = memoryStorage();
    const store = createWishlistStore(storage);
    assert.deepEqual(store.ids(), []);
    assert.deepEqual(store.toggle(12), [12]);
    assert.deepEqual(store.toggle(25), [25, 12]);
    const reopened = createWishlistStore(storage);
    assert.deepEqual(reopened.ids(), [25, 12]);
    assert.deepEqual(reopened.toggle(12), [25]);
    assert.equal(storage.getItem(WISHLIST_KEY), '[25]');
});

test('wishlist contains only IDs, never account names or prices', () => {
    const storage = memoryStorage();
    createWishlistStore(storage).toggle(31);
    assert.equal(storage.getItem(WISHLIST_KEY), '[31]');
});

for (const corrupt of ['{bad JSON', '{}', 'null', '123', '"text"']) {
    test(`corrupted storage recovers safely: ${corrupt}`, () => {
        const store = createWishlistStore(memoryStorage(corrupt));
        assert.deepEqual(store.ids(), []);
        assert.deepEqual(store.toggle(1), [1]);
    });
}

test('stored IDs are sanitised and deduplicated', () => {
    const store = createWishlistStore(memoryStorage('[1,1,2,"3",0,-2,null,1.5,9007199254740992]'));
    assert.deepEqual(store.ids(), [1, 2]);
});

test('limit protects storage while still allowing removal from a full wishlist', () => {
    const storage = memoryStorage(JSON.stringify(Array.from({ length: 100 }, (_, index) => index + 1)));
    const store = createWishlistStore(storage);
    assert.throws(() => store.toggle(101), RangeError);
    assert.equal(store.ids().length, 100);
    assert.equal(store.toggle(1).length, 99);
    assert.equal(store.toggle(101).length, 100);
});

test('invalid button IDs cannot be persisted', () => {
    const store = createWishlistStore(memoryStorage());
    for (const invalid of [0, -1, 1.5, '1', NaN, Infinity]) {
        assert.throws(() => store.toggle(invalid), TypeError);
    }
    assert.deepEqual(store.ids(), []);
});

test('failed writes do not pretend an account was saved', () => {
    const storage = memoryStorage('[1]');
    storage.setItem = () => { throw new Error('QuotaExceededError'); };
    const store = createWishlistStore(storage);
    assert.throws(() => store.toggle(2), /QuotaExceededError/);
    assert.deepEqual(store.ids(), [1]);
});

test('blocked reads are reported instead of silently erasing wishlist', () => {
    const store = createWishlistStore({ getItem() { throw new Error('SecurityError'); } });
    assert.throws(() => store.ids(), /SecurityError/);
});

test('another tab changing or clearing storage is visible on the next read', () => {
    const storage = memoryStorage('[1]');
    const store = createWishlistStore(storage);
    storage.setItem(WISHLIST_KEY, '[2,3]');
    assert.deepEqual(store.ids(), [2, 3]);
    storage.setItem(WISHLIST_KEY, '[]');
    assert.deepEqual(store.ids(), []);
});

test('lookup fetches current accounts and permits cleanup only after success', async () => {
    const storage = memoryStorage('[1,2,3]');
    const store = createWishlistStore(storage);
    const result = await fetchWishlist(store.ids(), 'https://example.com/wishlist/accounts', async (url, options) => {
        assert.deepEqual(url.searchParams.getAll('ids[]'), ['1', '2', '3']);
        assert.equal(options.cache, 'no-store');
        return { ok: true, json: async () => ({ ids: [1, 3], html: '<article>Terjual</article>' }) };
    });
    store.replace(result.ids);
    assert.deepEqual(store.ids(), [1, 3]);
});

test('failed lookup does not clear saved accounts', async () => {
    const store = createWishlistStore(memoryStorage('[1,2]'));
    await assert.rejects(fetchWishlist(store.ids(), 'https://example.com/wishlist/accounts', async () => ({ ok: false })), /tetap tersimpan/);
    assert.deepEqual(store.ids(), [1, 2]);
});

test('unexpected lookup IDs or malformed payloads are rejected', async () => {
    for (const result of [{ ids: [999], html: '' }, { ids: [1, 1], html: '' }, { ids: ['1'], html: '' }, { ids: [], html: null }]) {
        await assert.rejects(fetchWishlist([1], 'https://example.com/wishlist/accounts', async () => ({ ok: true, json: async () => result })), /tidak valid/);
    }
});
