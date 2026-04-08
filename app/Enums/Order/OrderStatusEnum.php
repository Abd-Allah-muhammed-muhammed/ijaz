<?php

namespace App\Enums\Order;

use App\Enums\Utilities\Collectable;
use App\Enums\Utilities\HasOperations;
use App\Enums\Utilities\Stringable;

enum OrderStatusEnum: string
{
    use Collectable, HasOperations, Stringable;

    case New = 'new';
    case Hold = 'hold';
    case OfferProvided = 'offer_provided';
    case PaymentCompleted = 'payment_completed';
    case InProgress = 'in_progress';
    case CancelledByProvider = 'cancelled_by_provider';
    case CancelledByClient = 'cancelled_by_client';
    case EndedByProvider = 'ended_by_provider';
    case EndedByClient = 'ended_by_client';
    case Refunded = 'refunded';

    public function toArray(): array
    {
        return [
            'label' => $this->toString(),
            'color' => $this->color(),
            'value' => $this->value,
        ];
    }

    public function color(): string
    {
        return match ($this) {
            self::New, self::Hold => 'primary',
            self::OfferProvided, self::InProgress => 'info',
            self::PaymentCompleted => 'warning',
            self::CancelledByClient, self::CancelledByProvider => 'danger',
            self::EndedByClient, self::EndedByProvider => 'success',
            self::Refunded => 'secondary',
        };
    }
}
