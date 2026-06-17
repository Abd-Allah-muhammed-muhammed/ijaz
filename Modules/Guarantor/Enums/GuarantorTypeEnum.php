<?php

namespace Modules\Guarantor\Enums;

use App\Enums\Utilities\Collectable;
use App\Enums\Utilities\HasOperations;
use App\Enums\Utilities\Stringable;

enum GuarantorTypeEnum: string
{
    use Collectable, HasOperations, Stringable;

    case Individual = 'individual';
    case Company = 'company';

    public function toString(): string
    {
        return __('guarantor.type.'.$this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::Individual => '#3b82f6',
            self::Company => '#8b5cf6',
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
