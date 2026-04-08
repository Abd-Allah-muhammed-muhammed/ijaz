<?php

namespace App\Enums\Providers;

use App\Enums\Utilities\Collectable;
use App\Enums\Utilities\HasOperations;
use App\Enums\Utilities\Stringable;

enum ProviderStatusEnum: string
{
    use Collectable, HasOperations, Stringable;

    case Pending = 'pending';
    case Approved = 'approved';
    case Suspended = 'suspended';
    case Rejected = 'rejected';
    case Blocked = 'blocked';

    /**
     * Returns an associative array with the following keys:
     * - value: string
     * - label: string
     * - color: string
     *
     * @return array{value: string, label: string, color: string}
     */
    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'label' => $this->label(),
            'color' => $this->color(),
        ];
    }

    public function label(): string
    {
        return trans($this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Approved => 'success',
            self::Suspended, self::Rejected, self::Blocked => 'danger',
        };
    }
}
