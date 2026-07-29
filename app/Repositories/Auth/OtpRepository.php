<?php

namespace App\Repositories\Auth;

use App\Contracts\Auth\OtpRepositoryInterface;
use App\Enums\Auth\OtpPurposeEnum;
use App\Models\Otp;
use Illuminate\Database\Eloquent\Model;

class OtpRepository implements OtpRepositoryInterface
{
    public function issueForSubject(Model $subject, OtpPurposeEnum $purpose, string $token): Otp
    {
        return Otp::query()->updateOrCreate(
            [
                'subject_type' => $subject->getMorphClass(),
                'subject_id' => $subject->getKey(),
                'purpose' => $purpose,
            ],
            [
                'phone' => null,
                'token' => $token,
                'expires_at' => now()->addMinutes($purpose->ttlMinutes()),
            ],
        );
    }

    public function issueForPhone(string $phone, OtpPurposeEnum $purpose, string $token): Otp
    {
        return Otp::query()->updateOrCreate(
            [
                'phone' => $phone,
                'purpose' => $purpose,
            ],
            [
                'subject_type' => null,
                'subject_id' => null,
                'token' => $token,
                'expires_at' => now()->addMinutes($purpose->ttlMinutes()),
            ],
        );
    }

    public function findForSubject(Model $subject, OtpPurposeEnum $purpose): ?Otp
    {
        return Otp::query()
            ->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', $subject->getKey())
            ->where('purpose', $purpose)
            ->first();
    }

    public function findForPhone(string $phone, OtpPurposeEnum $purpose): ?Otp
    {
        return Otp::query()
            ->whereNull('subject_id')
            ->where('phone', $phone)
            ->where('purpose', $purpose)
            ->first();
    }

    public function deleteForSubject(Model $subject, OtpPurposeEnum $purpose): void
    {
        Otp::query()
            ->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', $subject->getKey())
            ->where('purpose', $purpose)
            ->delete();
    }
}
