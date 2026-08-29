<?php

namespace Modules\Classifieds\Enums;

enum FuelTypeEnum: string
{
    case PETROL = 'petrol';
    case DIESEL = 'diesel';
    case ELECTRIC = 'electric';
    case HYBRID = 'hybrid';

    /**
     * @return array{label: string, value: string, color: string}
     */
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
        return __('advisement.fuel_type.'.$this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::PETROL => 'warning',
            self::DIESEL => 'secondary',
            self::ELECTRIC => 'success',
            self::HYBRID => 'info',
        };
    }
}
