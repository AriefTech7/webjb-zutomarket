import assert from 'node:assert/strict';
import test from 'node:test';
import { INSTALL_DISMISS_KEY, INSTALL_SNOOZE_MS, isIosSafari, shouldOfferInstall } from '../../resources/js/install.js';

const storageWith = (value) => ({ getItem: (key) => (key === INSTALL_DISMISS_KEY ? value : null) });
const now = 1_800_000_000_000;

test('shouldOfferInstall offers on a first visit and never when already installed', () => {
    assert.equal(shouldOfferInstall(storageWith(null), now, false), true);
    assert.equal(shouldOfferInstall(storageWith(null), now, true), false);
});

test('shouldOfferInstall waits seven days after "Nanti saja"', () => {
    assert.equal(shouldOfferInstall(storageWith(String(now - 1000)), now, false), false);
    assert.equal(shouldOfferInstall(storageWith(String(now - INSTALL_SNOOZE_MS - 1)), now, false), true);
});

test('shouldOfferInstall tolerates corrupt or blocked storage', () => {
    assert.equal(shouldOfferInstall(storageWith('bukan-angka'), now, false), true);
    assert.equal(shouldOfferInstall({ getItem: () => { throw new Error('blocked'); } }, now, false), true);
});

test('isIosSafari only matches Safari on Apple mobile devices', () => {
    assert.equal(isIosSafari('Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0 Mobile/15E148 Safari/604.1'), true);
    assert.equal(isIosSafari('Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/130.0 Mobile/15E148 Safari/604.1'), false);
    assert.equal(isIosSafari('Mozilla/5.0 (Linux; Android 14) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0 Mobile Safari/537.36'), false);
});
