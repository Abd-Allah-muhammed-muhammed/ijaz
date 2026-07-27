<?php

namespace App\Contracts\Auth;

use App\Enums\Auth\OtpPurposeEnum;
use App\Models\Otp;
use Illuminate\Database\Eloquent\Model;

interface OtpRepositoryInterface
{
    public function issueForSubject(Model $subject, OtpPurposeEnum $purpose, string $token): Otp;

    public function issueForPhone(string $phone, OtpPurposeEnum $purpose, string $token): Otp;

    public function findForSubject(Model $subject, OtpPurposeEnum $purpose): ?Otp;

    public function findForPhone(string $phone, OtpPurposeEnum $purpose): ?Otp;

    public function deleteForSubject(Model $subject, OtpPurposeEnum $purpose): void;
}
