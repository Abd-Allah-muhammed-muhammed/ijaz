<?php

namespace App\DTOs\Auth;

/**
 * Shared login/register challenge payload after an OtpSession is created.
 * Structurally identical for both flows — no Sanctum token, no user resource.
 */
final readonly class OtpChallengeResult
{
    public function __construct(
        public bool $success,
        public string $verificationId = '',
        public int $expiresIn = 0,
        public string $resendAvailableAt = '',
        public string $message = '',
        public int $statusCode = 200,
    ) {}

    public static function success(string $verificationId, int $expiresIn, string $resendAvailableAt): self
    {
        return new self(
            success: true,
            verificationId: $verificationId,
            expiresIn: $expiresIn,
            resendAvailableAt: $resendAvailableAt,
        );
    }

    public static function failure(string $message, int $statusCode): self
    {
        return new self(success: false, message: $message, statusCode: $statusCode);
    }

    /**
     * @return array{verification_id: string, expires_in: int, resend_available_at: string}
     */
    public function toData(): array
    {
        return [
            'verification_id' => $this->verificationId,
            'expires_in' => $this->expiresIn,
            'resend_available_at' => $this->resendAvailableAt,
        ];
    }
}
