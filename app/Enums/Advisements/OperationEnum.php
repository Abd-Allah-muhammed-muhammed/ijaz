<?php

namespace App\Enums\Advisements;

enum OperationEnum: string
{
    case SALE = 'sale';
    case RENT = 'rent';
    case BUY = 'buy';

    public function toArray(): array
    {
        return [
            'label' => $this->label(),
            'value' => $this->value,
            'color' => $this->color(),
        ];
    }

    public function label(): string
    {
        return __('advisement.operation.'.$this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::SALE => 'success',
            self::RENT => 'warning',
            self::BUY => 'primary',
        };
    }
}
