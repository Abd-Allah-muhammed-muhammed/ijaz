<?php

namespace App\DTOs\Auth;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Mirrors the exact return shape of the current OtpController::processCode()
 * (success/data/message/errors/token). A null OtpVerifyResult from the Action
 * represents the "wrong OTP" case that never reaches processCode() today.
 */
final readonly class OtpVerifyResult
{
    public function __construct(
        public bool $success,
        public ?JsonResource $data = null,
        public string $message = '',
        public array $errors = [],
        public string $token = '',
    ) {}
}
