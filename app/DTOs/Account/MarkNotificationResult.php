<?php

namespace App\DTOs\Account;

/**
 * Discriminated result of marking a single notification as read.
 *
 * Preserves Api\UserController::markAsRead() response shapes:
 * - ownership failure → 404 abort message
 * - already read → success message (idempotent)
 * - newly marked → success message
 */
final readonly class MarkNotificationResult
{
    public const STATUS_NOT_FOUND = 'not_found';

    public const STATUS_ALREADY_READ = 'already_read';

    public const STATUS_MARKED = 'marked';

    public function __construct(
        public string $status,
        public string $message,
        public int $statusCode = 200,
    ) {}

    public static function notFound(): self
    {
        return new self(
            status: self::STATUS_NOT_FOUND,
            message: 'Notification not found or already read.',
            statusCode: 404,
        );
    }

    public static function alreadyRead(): self
    {
        return new self(
            status: self::STATUS_ALREADY_READ,
            message: 'Notification already marked as read.',
        );
    }

    public static function marked(): self
    {
        return new self(
            status: self::STATUS_MARKED,
            message: 'Notification marked as read.',
        );
    }

    public function isNotFound(): bool
    {
        return $this->status === self::STATUS_NOT_FOUND;
    }
}
