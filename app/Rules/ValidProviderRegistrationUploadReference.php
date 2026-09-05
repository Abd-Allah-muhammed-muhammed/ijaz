<?php

namespace App\Rules;

use App\Contracts\Auth\ProviderRegistrationUploadRepositoryInterface;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Ensures an upload id belongs to the given token+field and is within retention.
 */
readonly class ValidProviderRegistrationUploadReference implements ValidationRule
{
    public function __construct(
        private string $token,
        private string $field,
    ) {}

    /**
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_numeric($value)) {
            $fail(__('provider_registration.upload_reference_invalid', ['field' => $this->field]));

            return;
        }

        $repository = app(ProviderRegistrationUploadRepositoryInterface::class);
        $upload = $repository->findByIdTokenAndField((int) $value, $this->token, $this->field);

        if ($upload === null) {
            $fail(__('provider_registration.upload_reference_missing', ['field' => __($this->field)]));

            return;
        }

        $retentionHours = (int) config('provider_registration.retention_hours');
        $expiresAt = $upload->created_at?->copy()->addHours($retentionHours);

        if ($expiresAt === null || $expiresAt->isPast()) {
            $fail(__('provider_registration.upload_reference_expired', ['field' => __($this->field)]));
        }
    }
}
