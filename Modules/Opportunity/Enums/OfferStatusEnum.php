<?php

namespace Modules\Opportunity\Enums;

use App\Enums\Utilities\Collectable;
use App\Enums\Utilities\HasOperations;
use App\Enums\Utilities\Stringable;

enum OfferStatusEnum: string
{
    use Collectable, HasOperations, Stringable;

    case Pending = 'pending';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function toString(): string
    {
        return __('opportunity.offer_status.'.$this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'primary',
            self::Accepted => 'success',
            self::Rejected, self::Cancelled => 'danger',
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
}
