<?php

namespace App\Contracts\OTPS;

use App\Models\VerificationCode;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

interface HasOTPsContract
{
    public function verificationCodes(): MorphMany;

    public function emailVerificationCode(): MorphOne;

    public function phoneVerificationCode(): MorphOne;

    public function passwordVerificationCode(): MorphOne;

    public function passwordRestCode(): MorphOne;

    public function loginVerificationCode(): MorphOne;

    public function updateOrCreateVerificationCode(string $token, string $type = 'email', int $ttl = 30): VerificationCode;

    public function markEmailAsVerified();

    public function markPhoneAsVerified();

    public function markLoginAsVerified(bool $token = true);
}
