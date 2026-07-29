<?php

namespace App\Contracts\Auth;

use App\Enums\Auth\OtpPurposeEnum;
use App\Models\Otp;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

interface HasOTPsContract
{
    public function otps(): MorphMany;

    public function verificationCodes(): MorphMany;

    public function emailVerificationCode(): MorphOne;

    public function phoneVerificationCode(): MorphOne;

    public function passwordVerificationCode(): MorphOne;

    public function passwordResetCode(): MorphOne;

    public function loginVerificationCode(): MorphOne;

    public function updateOrCreateVerificationCode(string $token, string|OtpPurposeEnum $type = 'email'): Otp;

    public function markEmailAsVerified();

    public function markPhoneAsVerified(): bool;

    public function markLoginAsVerified(bool $token = true);
}
