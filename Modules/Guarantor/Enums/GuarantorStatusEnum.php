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
    case Disputed = 'disputed';
    case Ended = 'ended';
    case EndedViaDispute = 'ended_via_dispute';
    case Cancelled = 'cancelled';
    case CancelledViaDispute = 'cancelled_via_dispute';
    case Escalated = 'escalated';
    case Settled = 'settled';
    case Withdrawn = 'withdrawn';

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
            self::Disputed => '#dc2626',
            self::Ended => '#10b981',
            self::EndedViaDispute => '#10b981',
            self::Cancelled => '#6b7280',
            self::CancelledViaDispute => '#6b7280',
            self::Escalated => '#7c3aed',
            self::Settled => '#0d9488',
            self::Withdrawn => '#6366f1',
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
        return in_array($this, self::terminalCases(), true);
    }

    /**
     * @return list<self>
     */
    public static function terminalCases(): array
    {
        return [
            self::RejectedByAdmin,
            self::Rejected,
            self::Ended,
            self::EndedViaDispute,
            self::Cancelled,
            self::CancelledViaDispute,
            self::Escalated,
            self::Settled,
            self::Withdrawn,
        ];
    }

    /**
     * @return list<string>
     */
    public static function terminalValues(): array
    {
        return array_map(
            static fn (self $status): string => $status->value,
            self::terminalCases(),
        );
    }

    /**
     * @return list<string>
     */
    public static function endedAggregateValues(): array
    {
        return [
            self::Ended->value,
            self::EndedViaDispute->value,
        ];
    }

    /**
     * @return list<string>
     */
    public static function cancelledAggregateValues(): array
    {
        return [
            self::Cancelled->value,
            self::CancelledViaDispute->value,
        ];
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
                // Accept requires POST .../accept with counterparty_signature —
                // not the generic /status endpoint.
                self::ApprovedByAdmin => $new === self::Rejected,
                self::InProgress,
                self::Overdue => $new === self::Ended
                    || $new === self::Disputed,
                default => false,
            },
            'requester' => match ($old) {
                self::InProgress,
                self::Overdue => $new === self::Ended
                    || $new === self::Disputed,
                default => false,
            },
            default => false,
        };
    }
}
