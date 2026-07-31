<?php

use App\Support\Normalize;

test('Normalize::westernDigits converts Arabic-Indic digits to Western digits', function () {
    expect(Normalize::westernDigits('٢٠٢٦-٠٨-٠١'))->toBe('2026-08-01');
});

test('Normalize::westernDigits converts Persian digits to Western digits', function () {
    expect(Normalize::westernDigits('۲۰۲۶-۰۸-۰۱'))->toBe('2026-08-01');
});

test('Normalize::westernDigits leaves Western digits unchanged and preserves null', function () {
    expect(Normalize::westernDigits('2026-08-01'))->toBe('2026-08-01')
        ->and(Normalize::westernDigits(null))->toBeNull()
        ->and(Normalize::westernDigits(''))->toBe('');
});
