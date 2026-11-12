import assert from 'node:assert/strict';
import test from 'node:test';
import { caretAfterDigits, formatThousands } from '../../resources/js/money.js';

test('formatThousands groups rupiah amounts with dots', () => {
    assert.equal(formatThousands('1000000'), '1.000.000');
    assert.equal(formatThousands('350000'), '350.000');
    assert.equal(formatThousands('999'), '999');
    assert.equal(formatThousands(25000), '25.000');
});

test('formatThousands ignores existing dots, letters and leading zeros', () => {
    assert.equal(formatThousands('1.000.0000'), '10.000.000');
    assert.equal(formatThousands('Rp 12.5a00'), '12.500');
    assert.equal(formatThousands('0007500'), '7.500');
    assert.equal(formatThousands('0'), '0');
    assert.equal(formatThousands(''), '');
    assert.equal(formatThousands(null), '');
});

test('caretAfterDigits keeps the caret after the same digit', () => {
    assert.equal(caretAfterDigits('1.000.000', 1), 1);
    assert.equal(caretAfterDigits('1.000.000', 4), 5);
    assert.equal(caretAfterDigits('1.000.000', 7), 9);
    assert.equal(caretAfterDigits('1.000', 0), 0);
    assert.equal(caretAfterDigits('1.000', 9), 5);
});
