<?php

namespace App\DTOs\Auth;

/**
 * Discriminated result of a User login attempt.
 *
 * The current AuthController::login() returns either a token-bearing success
 * response or a failedMessageResponse($message, $statusCode) for the
 * "user not found" / "status blocked" domain cases. Since HasApiResponse's
 * response builders live on (and are private to) the controller, the Action
 * returns this discriminated result and the controller maps it to the exact
 * same response shape/status codes.
 */
final readonly class UserLoginResult
{
    public function __construct(
        public bool $success,
        public string $token = '',
        public string $message = '',
        public int $statusCode = 200,
    ) {}

    public static function success(string $token): self
    {
        return new self(success: true, token: $token);
    }

    public static function failure(string $message, int $statusCode): self
    {
        return new self(success: false, message: $message, statusCode: $statusCode);
    }
}
