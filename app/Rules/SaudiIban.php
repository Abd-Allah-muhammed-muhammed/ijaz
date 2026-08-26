<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Saudi IBAN format validation (SA + 22 digits). Does not verify bank account existence.
 */
readonly class SaudiIban implements ValidationRule
{
    /**
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail(__('guarantor.invalid_saudi_iban'));

            return;
        }

        $normalized = strtoupper(preg_replace('/\s+/', '', $value) ?? '');

        if (! preg_match('/^SA\d{22}$/', $normalized)) {
            $fail(__('guarantor.invalid_saudi_iban'));
        }
    }
}
