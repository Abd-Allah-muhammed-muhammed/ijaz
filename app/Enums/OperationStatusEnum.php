<?php

namespace App\Enums;

use App\Enums\Utilities\HasOperations;

enum OperationStatusEnum: string
{
    use HasOperations;

    case Pending = 'pending';
    case Rejected = 'rejected';

    case Approved = 'approved';

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
            self::Approved => 'success',
            self::Rejected => 'danger'
        };
    }

    public function isPending(): bool
    {
        return $this->is(self::Pending);
    }

    public function isApproved(): bool
    {
        return $this->is(self::Approved);
    }

    public function isRejected(): bool
    {
        return $this->is(self::Rejected);
    }
}
