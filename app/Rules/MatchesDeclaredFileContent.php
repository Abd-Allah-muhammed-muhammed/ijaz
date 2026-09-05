<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Rejects uploads whose sniffed content MIME does not match an allowed list.
 * Complements Laravel's client/extension-oriented mime rules with finfo sniffing.
 */
readonly class MatchesDeclaredFileContent implements ValidationRule
{
    /**
     * @param  list<string>  $allowedMimeTypes
     */
    public function __construct(
        private array $allowedMimeTypes,
    ) {}

    /**
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile || ! $value->isValid()) {
            $fail(__('validation.file', ['attribute' => $attribute]));

            return;
        }

        $path = $value->getRealPath();

        if ($path === false || $path === '') {
            $fail(__('validation.file', ['attribute' => $attribute]));

            return;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $detected = $finfo->file($path);

        if (! is_string($detected) || $detected === '') {
            $fail(__('validation.mimetypes', [
                'attribute' => $attribute,
                'values' => implode(', ', $this->allowedMimeTypes),
            ]));

            return;
        }

        if (! $this->matchesAllowed($detected)) {
            $fail(__('validation.mimetypes', [
                'attribute' => $attribute,
                'values' => implode(', ', $this->allowedMimeTypes),
            ]));
        }
    }

    private function matchesAllowed(string $detected): bool
    {
        $detected = strtolower($detected);

        foreach ($this->allowedMimeTypes as $allowed) {
            $allowed = strtolower($allowed);

            if (str_ends_with($allowed, '/*')) {
                $prefix = substr($allowed, 0, -1);

                if (str_starts_with($detected, $prefix)) {
                    return true;
                }

                continue;
            }

            if ($detected === $allowed) {
                return true;
            }
        }

        return false;
    }
}
