<?php

namespace Modules\Guarantor\Enums;

use App\Enums\Utilities\Collectable;
use App\Enums\Utilities\HasOperations;
use App\Enums\Utilities\Stringable;

enum InstallmentStatusEnum: string
{
    use Collectable, HasOperations, Stringable;

    case Pending = 'pending';
    case Paid = 'paid';
    case Released = 'released';
    case Overdue = 'overdue';
    case Refunded = 'refunded';

    public function toString(): string
    {
        return __('guarantor.installment_status.'.$this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => '#f59e0b',
            self::Paid => '#3b82f6',
            self::Released => '#10b981',
            self::Overdue => '#ef4444',
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
}
