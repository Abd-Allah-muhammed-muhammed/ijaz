<?php

namespace Modules\Opportunity\Enums;

use App\Enums\Utilities\Collectable;
use App\Enums\Utilities\HasOperations;
use App\Enums\Utilities\Stringable;

enum OpportunityStatusEnum: string
{
    use Collectable, HasOperations, Stringable;

    case New = 'new';
    case OfferAccepted = 'offer_accepted';
    case InProgress = 'in_progress';
    case Ended = 'ended';
    case Cancelled = 'cancelled';

    public function toString(): string
    {
        return __('opportunity.status.'.$this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::New => 'primary',
            self::OfferAccepted, self::InProgress => 'info',
            self::Ended => 'success',
            self::Cancelled => 'danger',
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
        if ($actor === 'author') {
            return match ($old) {
                self::New => in_array($new, [self::OfferAccepted, self::Cancelled], true),
                self::OfferAccepted => $new === self::Cancelled,
                self::InProgress => in_array($new, [self::Ended, self::Cancelled], true),
                default => false,
            };
        }

        return match ($old) {
            self::OfferAccepted => $new === self::Cancelled,
            self::InProgress => in_array($new, [self::Ended, self::Cancelled], true),
            default => false,
        };
    }
}
