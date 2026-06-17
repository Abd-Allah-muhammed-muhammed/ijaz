<?php

namespace Modules\Guarantor\Enums;

use App\Enums\Utilities\Collectable;
use App\Enums\Utilities\HasOperations;
use App\Enums\Utilities\Stringable;

enum GuarantorStatusEnum: string
{
    use Collectable, HasOperations, Stringable;

    case New = 'new';
    case Approved = 'approved';
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
            self::New => '#22c55e',
            self::Approved => '#3b82f6',
            self::Rejected => '#f97316',
            self::InProgress => '#06b6d4',
            self::Overdue => '#f59e0b',
            self::Ended => '#10b981',
            self::Cancelled => '#ef4444',
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

    public static function isAllowed(self $old, self $new, string $actor): bool
    {
        return match ($actor) {
            'requester' => match ($old) {
                self::New, self::Approved => $new === self::Cancelled,
                self::InProgress, self::Overdue => $new === self::Ended
                    || $new === self::Cancelled,
                default => false,
            },
            'counterparty' => match ($old) {
                self::New => $new === self::Approved
                    || $new === self::Rejected,
                self::Approved => $new === self::Cancelled,
                self::InProgress, self::Overdue => $new === self::Ended,
                default => false,
            },
            'admin' => true,
            default => false,
        };
    }
}
