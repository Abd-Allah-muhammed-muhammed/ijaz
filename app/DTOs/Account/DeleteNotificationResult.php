<?php

namespace App\DTOs\Account;

/**
 * Discriminated result of deleting a single notification.
 *
 * Preserves Api\UserController::deleteNotification() response shapes:
 * - ownership failure → 404 abort message
 * - deleted → success message
 */
final readonly class DeleteNotificationResult
{
    public const STATUS_NOT_FOUND = 'not_found';

    public const STATUS_DELETED = 'deleted';

    public function __construct(
        public string $status,
        public string $message,
        public int $statusCode = 200,
    ) {}

    public static function notFound(): self
    {
        return new self(
            status: self::STATUS_NOT_FOUND,
            message: 'Notification not found',
            statusCode: 404,
        );
    }

    public static function deleted(): self
    {
        return new self(
            status: self::STATUS_DELETED,
            message: 'Notification deleted successfully.',
        );
    }

    public function isNotFound(): bool
    {
        return $this->status === self::STATUS_NOT_FOUND;
    }
}
