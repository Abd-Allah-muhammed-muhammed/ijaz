<?php

namespace App\Enums\Order;

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
    case Paid = 'paid';

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
            self::Pending => 'primary',
            self::Accepted,self::Paid => 'success',
            self::Rejected, self::Cancelled => 'danger'
        };

    }
}
