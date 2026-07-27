<?php

namespace App\Traits;

use App\Contracts\Auth\OtpRepositoryInterface;
use App\Enums\Auth\OtpPurposeEnum;
use App\Models\Otp;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait HasOTPs
{
    public static function bootHasOTPs(): void
    {
        static::deleted(function ($model) {
            $model->otps()->delete();
        });
    }

    public function otps(): MorphMany
    {
        return $this->morphMany(Otp::class, 'subject');
    }

    /**
     * @deprecated Use otps() — kept for call-site compatibility during OTP unification.
     */
    public function verificationCodes(): MorphMany
    {
        return $this->otps();
    }

    public function emailVerificationCode(): MorphOne
    {
        return $this->morphOne(Otp::class, 'subject')->withAttributes(['purpose' => OtpPurposeEnum::Email]);
    }

    public function phoneVerificationCode(): MorphOne
    {
        return $this->morphOne(Otp::class, 'subject')->withAttributes(['purpose' => OtpPurposeEnum::Phone]);
    }

    public function passwordVerificationCode(): MorphOne
    {
        return $this->morphOne(Otp::class, 'subject')->withAttributes(['purpose' => OtpPurposeEnum::Password]);
    }

    public function loginVerificationCode(): MorphOne
    {
        return $this->morphOne(Otp::class, 'subject')->withAttributes(['purpose' => OtpPurposeEnum::Login]);
    }

    public function passwordRestCode(): MorphOne
    {
        return $this->morphOne(Otp::class, 'subject')->withAttributes(['purpose' => OtpPurposeEnum::PasswordReset]);
    }

    public function updateOrCreateVerificationCode(string $token, string|OtpPurposeEnum $type = 'email'): Otp
    {
        $purpose = $type instanceof OtpPurposeEnum ? $type : OtpPurposeEnum::from($type);

        return app(OtpRepositoryInterface::class)->issueForSubject($this, $purpose, $token);
    }

    public function markLoginAsVerified(bool $token = true): ?string
    {
        app(OtpRepositoryInterface::class)->deleteForSubject($this, OtpPurposeEnum::Login);

        if ($token) {
            $this->tokens()->delete();
            $plainTextToken = $this->createToken('user-app', ['*'])->plainTextToken;

            return explode('|', $plainTextToken)[1];
        }

        return null;
    }

    public function markPhoneAsVerified(): bool
    {
        return $this->forceFill(['phone_verified_at' => now()])->save();
    }
}
