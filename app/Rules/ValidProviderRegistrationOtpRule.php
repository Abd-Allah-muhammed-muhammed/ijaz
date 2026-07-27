<?php

namespace App\Rules;

use App\Contracts\Auth\OtpRepositoryInterface;
use App\Enums\Auth\OtpPurposeEnum;
use App\Support\Phone;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

final class ValidProviderRegistrationOtpRule implements ValidationRule
{
    /**
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $phone = Phone::make(request()->input('phone'))->toString();
        $otp = app(OtpRepositoryInterface::class)->findForPhone(
            $phone,
            OtpPurposeEnum::ProviderRegistration,
        );

        if (! $otp || $otp->isExpired()) {
            $fail(trans('auth.otp_expired'));

            return;
        }

        if (! $otp->matches((string) $value)) {
            $fail(trans('auth.otp_invalid'));
        }
    }
}
