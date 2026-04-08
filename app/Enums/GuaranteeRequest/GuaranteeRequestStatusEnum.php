<?php

namespace App\Enums\GuaranteeRequest;

use App\Enums\Utilities\Collectable;
use App\Enums\Utilities\HasOperations;
use App\Enums\Utilities\Stringable;

enum GuaranteeRequestStatusEnum: string
{
    use Collectable , HasOperations , Stringable;

    case New = 'new';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case InProgress = 'in_progress';

    case CancelledByProvider = 'cancelled_by_provider';
    case CancelledByClient = 'cancelled_by_client';
    case EndedByProvider = 'ended_by_provider';
    case EndedByClient = 'ended_by_client';
    case Refunded = 'refunded';

    public function color(): string
    {
        return match ($this) {
            self::New => 'primary',
            self::InProgress, self::Approved => 'info',
            self::Rejected => 'warning',
            self::CancelledByClient, self::CancelledByProvider => 'danger',
            self::EndedByClient, self::EndedByProvider => 'success',
            self::Refunded => 'secondary',
        };
    }

    public function toArray(): array
    {
        return [
            'label' => $this->toString(),
            'color' => $this->color(),
            'value' => $this->value,
        ];
    }

    public static function isAllowed(self $oldStatus, self $newStatus, $type = 'user'): bool
    {

        if ($type === 'user') {
            return match ($oldStatus) {
                self::New, self::Approved => $newStatus === self::CancelledByClient,
                self::InProgress => $newStatus === self::EndedByClient,
                default => false,
            };
        } else {
            return match ($oldStatus) {
                self::New => in_array($newStatus, [self::Approved, self::Rejected]),
                self::Approved => $newStatus === self::CancelledByProvider,
                self::InProgress => $newStatus === self::EndedByProvider,
                default => false,
            };
        }
    }
}
