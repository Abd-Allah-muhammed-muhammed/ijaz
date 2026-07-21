<?php

namespace Modules\Support\Enums;

use App\Enums\Utilities\HasOperations;

enum TicketSupportStatusEnum: string
{
    use HasOperations;

    case Pending = 'pending';
    case Open = 'open';
    case Closed = 'closed';

    public function toArray(): array
    {
        return [
            'label' => $this->toString(),
            'color' => $this->color(),
            'value' => $this->value,
        ];
    }

    public function toString(): string
    {
        return trans(strtolower($this->value));
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'primary',
            self::Open => 'success',
            self::Closed => 'danger'
        };
    }
}
