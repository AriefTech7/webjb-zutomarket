// Rupiah inputs in the admin: typing 1000000 shows 1.000.000. The server strips the dots again.

export function formatThousands(value) {
    const digits = String(value ?? '').replace(/\D/g, '').replace(/^0+(?=\d)/, '');

    return digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

// Where the caret belongs after formatting: right after the same number of digits as before.
export function caretAfterDigits(formatted, digitCount) {
    if (digitCount <= 0) return 0;
    let seen = 0;
    for (let i = 0; i < formatted.length; i++) {
        if (/\d/.test(formatted[i]) && ++seen === digitCount) return i + 1;
    }

    return formatted.length;
}

export function initialiseMoneyInputs(root = document) {
    root.querySelectorAll('input[data-money]').forEach((input) => {
        input.value = formatThousands(input.value);
        input.addEventListener('input', () => {
            const caret = input.selectionStart ?? input.value.length;
            const digitsBeforeCaret = input.value.slice(0, caret).replace(/\D/g, '').length;
            const formatted = formatThousands(input.value);
            if (formatted === input.value) return;
            input.value = formatted;
            const position = caretAfterDigits(formatted, digitsBeforeCaret);
            input.setSelectionRange?.(position, position);
        });
    });
}
