<?php

namespace App\Repositories\Auth;

use App\Contracts\Auth\OtpSessionRepositoryInterface;
use App\Enums\Auth\OtpPurposeEnum;
use App\Models\OtpSession;
use Illuminate\Database\Eloquent\Model;

class OtpSessionRepository implements OtpSessionRepositoryInterface
{
    public function createForUser(Model $user, OtpPurposeEnum $purpose, int $ttlMinutes): OtpSession
    {
        return OtpSession::query()->create([
            'user_id' => $user->getKey(),
            'purpose' => $purpose,
            'attempts_count' => 0,
            'max_attempts' => (int) config('otp.max_verification_attempts', 5),
            'expires_at' => now()->addMinutes($ttlMinutes),
        ]);
    }

    public function findById(string $id): ?OtpSession
    {
        return OtpSession::query()->find($id);
    }

    public function incrementAttempts(OtpSession $session): OtpSession
    {
        $session->incrementAttempts();

        return $session->refresh();
    }

    public function deleteForUser(Model $user, OtpPurposeEnum $purpose): void
    {
        OtpSession::query()
            ->where('user_id', $user->getKey())
            ->where('purpose', $purpose)
            ->delete();
    }

    public function extendExpiry(OtpSession $session, int $ttlMinutes): OtpSession
    {
        $session->forceFill([
            'expires_at' => now()->addMinutes($ttlMinutes),
        ])->save();

        return $session->refresh();
    }

    public function deleteExpired(): int
    {
        return OtpSession::query()
            ->where('expires_at', '<=', now())
            ->delete();
    }
}
