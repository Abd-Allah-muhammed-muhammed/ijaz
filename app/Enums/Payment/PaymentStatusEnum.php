<?php

namespace App\Enums\Payment;

use App\Enums\Utilities\Collectable;
use App\Enums\Utilities\HasOperations;
use App\Enums\Utilities\Stringable;

enum PaymentStatusEnum: string
{
    use Collectable,HasOperations , Stringable;

    case Pending = 'pending';
    case Accepted = 'accepted';

    case Canceled = 'canceled';
    case Rejected = 'rejected';

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
            self::Accepted => 'success',
            self::Canceled, self::Rejected => 'danger'
        };
    }
}
