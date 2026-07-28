<?php

namespace App\DTOs\Auth;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Result of verifying an OtpSession (login/register) or an authenticated purpose OTP.
 *
 * Session-flow failures use $errorCode (invalid_code | max_attempts_exceeded |
 * verification_expired) with HTTP 422. Success for session flow carries accessToken.
 */
final readonly class OtpVerifyResult
{
    public function __construct(
        public bool $success,
        public ?JsonResource $data = null,
        public string $message = '',
        public array $errors = [],
        public string $token = '',
        public string $accessToken = '',
        public string $tokenType = 'Bearer',
        public string $errorCode = '',
        public ?int $attemptsRemaining = null,
        public int $statusCode = 200,
    ) {}

    public static function sessionSuccess(string $accessToken, JsonResource $user): self
    {
        return new self(
            success: true,
            data: $user,
            accessToken: $accessToken,
            token: $accessToken,
        );
    }

    public static function failure(string $errorCode, string $message = '', ?int $attemptsRemaining = null): self
    {
        return new self(
            success: false,
            message: $message,
            errorCode: $errorCode,
            attemptsRemaining: $attemptsRemaining,
            statusCode: 422,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toErrorData(): array
    {
        $data = ['code' => $this->errorCode];

        if ($this->attemptsRemaining !== null) {
            $data['attempts_remaining'] = $this->attemptsRemaining;
        }

        return $data;
    }

    /**
     * @return array{access_token: string, token_type: string, user: JsonResource}
     */
    public function toSuccessData(): array
    {
        return [
            'access_token' => $this->accessToken,
            'token_type' => $this->tokenType,
            'user' => $this->data,
        ];
    }
}
