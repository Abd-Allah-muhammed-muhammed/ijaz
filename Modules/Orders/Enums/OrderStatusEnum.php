<?php

namespace Modules\Orders\Enums;

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
    case Disputed = 'disputed';
    case Cancelled = 'cancelled';
    case CancelledByProvider = 'cancelled_by_provider';
    case CancelledByClient = 'cancelled_by_client';
    case CancelledViaDispute = 'cancelled_via_dispute';
    case EndedByProvider = 'ended_by_provider';
    case EndedByClient = 'ended_by_client';
    case EndedViaDispute = 'ended_via_dispute';
    case Escalated = 'escalated';
    case Settled = 'settled';

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
            self::Disputed => 'danger',
            self::Cancelled, self::CancelledByClient, self::CancelledByProvider, self::CancelledViaDispute => 'danger',
            self::EndedByClient, self::EndedByProvider, self::EndedViaDispute => 'success',
            self::Escalated => 'dark',
            self::Settled => 'success',
        };
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
            self::Cancelled,
            self::CancelledByProvider,
            self::CancelledByClient,
            self::CancelledViaDispute,
            self::EndedByProvider,
            self::EndedByClient,
            self::EndedViaDispute,
            self::Escalated,
            self::Settled,
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
            self::EndedByProvider->value,
            self::EndedByClient->value,
            self::EndedViaDispute->value,
            self::Settled->value,
        ];
    }

    /**
     * @return list<string>
     */
    public static function cancelledAggregateValues(): array
    {
        return [
            self::Cancelled->value,
            self::CancelledByProvider->value,
            self::CancelledByClient->value,
            self::CancelledViaDispute->value,
            self::Escalated->value,
        ];
    }

    public static function isAllowed(self $old, self $new, string $actor): bool
    {
        if ($old->isTerminal()) {
            return false;
        }

        if ($old === $new) {
            return false;
        }

        return match ($actor) {
            'provider' => $old === self::InProgress && $new === self::CancelledByProvider,
            'user' => $old === self::InProgress && $new === self::CancelledByClient,
            'user_dispute', 'provider_dispute' => $old === self::InProgress && $new === self::Disputed,
            default => false,
        };
    }
}
