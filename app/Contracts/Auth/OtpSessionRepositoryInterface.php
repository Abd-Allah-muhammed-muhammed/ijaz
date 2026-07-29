<?php

namespace App\Contracts\Auth;

use App\Enums\Auth\OtpPurposeEnum;
use App\Models\OtpSession;
use Illuminate\Database\Eloquent\Model;

interface OtpSessionRepositoryInterface
{
    public function createForUser(Model $user, OtpPurposeEnum $purpose, int $ttlMinutes): OtpSession;

    public function findById(string $id): ?OtpSession;

    public function incrementAttempts(OtpSession $session): OtpSession;

    public function deleteForUser(Model $user, OtpPurposeEnum $purpose): void;

    public function extendExpiry(OtpSession $session, int $ttlMinutes): OtpSession;

    public function deleteExpired(): int;
}
