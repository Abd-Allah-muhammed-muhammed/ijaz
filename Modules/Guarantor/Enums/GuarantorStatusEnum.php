<?php

namespace Modules\Guarantor\Enums;

use App\Enums\Utilities\Collectable;
use App\Enums\Utilities\HasOperations;
use App\Enums\Utilities\Stringable;

enum GuarantorStatusEnum: string
{
    use Collectable, HasOperations, Stringable;

    case New = 'new';
    case PendingAdmin = 'pending_admin';
    case ApprovedByAdmin = 'approved_by_admin';
    case RejectedByAdmin = 'rejected_by_admin';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case InProgress = 'in_progress';
    case Overdue = 'overdue';
    case Ended = 'ended';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';

    public function toString(): string
    {
        return __('guarantor.status.'.$this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::New => '#6b7280',
            self::PendingAdmin => '#f59e0b',
            self::ApprovedByAdmin => '#3b82f6',
            self::RejectedByAdmin => '#ef4444',
            self::Accepted => '#8b5cf6',
            self::Rejected => '#f97316',
            self::InProgress => '#06b6d4',
            self::Overdue => '#ef4444',
            self::Ended => '#10b981',
            self::Cancelled => '#6b7280',
            self::Refunded => '#6b7280',
        };
    }

    /**
     * @return array{value: string, label: string, color: string}
     */
    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'label' => $this->toString(),
            'color' => $this->color(),
        ];
    }

    public function isTerminal(): bool
    {
        return in_array($this, [
            self::RejectedByAdmin,
            self::Rejected,
            self::Ended,
            self::Cancelled,
            self::Refunded,
        ], true);
    }

    public static function isAllowed(self $old, self $new, string $actor): bool
    {
        if ($actor === 'admin') {
            return true;
        }

        if ($old->isTerminal()) {
            return false;
        }

        if ($old === $new) {
            return false;
        }

        return match ($actor) {
            'counterparty' => match ($old) {
                self::ApprovedByAdmin => $new === self::Accepted
                    || $new === self::Rejected,
                self::InProgress,
                self::Overdue => $new === self::Ended,
                default => false,
            },
            'requester' => match ($old) {
                self::InProgress,
                self::Overdue => $new === self::Ended,
                default => false,
            },
            default => false,
        };
    }
}
