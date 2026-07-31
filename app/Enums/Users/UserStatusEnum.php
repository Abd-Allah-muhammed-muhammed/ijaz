<?php

namespace App\Enums\Users;

use App\Enums\Utilities\Collectable;
use App\Enums\Utilities\HasOperations;
use App\Enums\Utilities\Stringable;

enum UserStatusEnum: string
{
    use Collectable, HasOperations, Stringable;

    case Active = 'active';
    case Blocked = 'blocked';
    case Deleted = 'deleted';

    /**
     * Returns an associative array with the following keys:
     * - value: string
     * - label: string
     * - color: string
     *
     * @return array{value: string, label: string, color: string}
     */
    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'label' => $this->label(),
            'color' => $this->color(),
        ];
    }

    public function label(): string
    {
        return trans($this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'warning',
            self::Blocked, self::Deleted => 'danger',
        };
    }

    /**
     * Message shown when a user with this status is denied authentication,
     * or null when the status permits access. Shared by login, OTP verify,
     * and the per-request EnsureUserIsActive middleware.
     */
    public function authRejectionMessage(bool $isTemporaryBlock): ?string
    {
        return match ($this) {
            self::Active => null,
            self::Deleted => __('auth.deleted'),
            self::Blocked => $isTemporaryBlock ? __('auth.blocked') : __('auth.banned'),
        };
    }
}
