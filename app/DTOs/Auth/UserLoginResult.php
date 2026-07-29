<?php

namespace App\DTOs\Auth;

/**
 * @deprecated Use OtpChallengeResult — kept as a type alias name during the auth overhaul.
 * Login now returns OtpChallengeResult from UserAuthService::login().
 */
final readonly class UserLoginResult
{
    public function __construct(
        public bool $success,
        public string $verificationId = '',
        public int $expiresIn = 0,
        public string $resendAvailableAt = '',
        public string $message = '',
        public int $statusCode = 200,
    ) {}

    public static function fromChallenge(OtpChallengeResult $challenge): self
    {
        return new self(
            success: $challenge->success,
            verificationId: $challenge->verificationId,
            expiresIn: $challenge->expiresIn,
            resendAvailableAt: $challenge->resendAvailableAt,
            message: $challenge->message,
            statusCode: $challenge->statusCode,
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
