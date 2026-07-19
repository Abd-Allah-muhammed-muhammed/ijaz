<?php

namespace App\Traits;

use App\Models\VerificationCode;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait HasOTPs
{
    public static function bootHasOTPs(): void
    {
        static::deleted(function ($model) {
            $model->verificationCodes()->delete();
        });

    }

    public function verificationCodes(): MorphMany
    {
        return $this->morphMany(VerificationCode::class, 'user');
    }

    public function emailVerificationCode(): MorphOne
    {
        return $this->morphOne(VerificationCode::class, 'user')->withAttributes(['type' => 'email']);
    }

    public function phoneVerificationCode(): MorphOne
    {
        return $this->morphOne(VerificationCode::class, 'user')->withAttributes(['type' => 'phone']);
    }

    public function passwordVerificationCode(): MorphOne
    {
        return $this->morphOne(VerificationCode::class, 'user')->withAttributes(['type' => 'password']);
    }

    public function loginVerificationCode(): MorphOne
    {
        return $this->morphOne(VerificationCode::class, 'user')->withAttributes(['type' => 'login']);
    }

    public function passwordRestCode(): MorphOne
    {
        return $this->morphOne(VerificationCode::class, 'user')->withAttributes(['type' => 'password_reset']);
    }

    public function updateOrCreateVerificationCode(string $token, string $type = 'email', int $ttl = 30): VerificationCode
    {
        $data = [
            'token' => $token,
            'expire_at' => now()->addMinutes($ttl),
        ];

        return $this->verificationCodes()->updateOrCreate(['type' => $type], $data);
    }

    public function markLoginAsVerified(bool $token = true): ?string
    {
        $this->loginVerificationCode()->delete(); // Delete previous login verification code
        if ($token) {
            $this->tokens()->delete(); // Delete all previous tokens
            $token = $this->createToken('user-app', ['*'])->plainTextToken;

            return explode('|', $token)[1];
        }

        return null;
    }

    public function markPhoneAsVerified(): bool
    {
        return $this->forceFill(['phone_verified_at' => now()])->save();
    }
}
