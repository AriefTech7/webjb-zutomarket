import assert from 'node:assert/strict';
import test from 'node:test';
import { PROFILE_KEY, readProfile, withProfile } from '../../resources/js/profile.js';

const storageWith = (value) => ({ getItem: (key) => (key === PROFILE_KEY ? value : null) });

test('readProfile returns blanks for missing or corrupt data', () => {
    assert.deepEqual(readProfile(storageWith(null)), { name: '', phone: '' });
    assert.deepEqual(readProfile(storageWith('{broken')), { name: '', phone: '' });
    assert.deepEqual(readProfile(storageWith('null')), { name: '', phone: '' });
    assert.deepEqual(readProfile(storageWith('{"name":1}')), { name: '', phone: '' });
});

test('withProfile leaves the link unchanged when the profile is empty', () => {
    const href = 'https://wa.me/628123?text=Halo%20admin';

    assert.equal(withProfile(href, { name: '', phone: '' }), href);
});

test('withProfile appends the profile to the existing message and encodes spaces as %20', () => {
    const result = withProfile('https://wa.me/628123?text=Halo%20admin', { name: 'Budi & Co', phone: '0812' });

    assert.equal(result, `https://wa.me/628123?text=${encodeURIComponent('Halo admin\nNama: Budi & Co\nNo. WhatsApp: 0812')}`);
    assert.ok(!result.includes('+'));
});

test('withProfile works on a link with no message', () => {
    assert.equal(withProfile('https://wa.me/628123', { name: 'Budi', phone: '' }), `https://wa.me/628123?text=${encodeURIComponent('Nama: Budi')}`);
});
