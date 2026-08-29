<?php

namespace Modules\Classifieds\Enums;

enum TransmissionEnum: string
{
    case AUTOMATIC = 'automatic';
    case MANUAL = 'manual';

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
        return __('advisement.transmission.'.$this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::AUTOMATIC => 'info',
            self::MANUAL => 'primary',
        };
    }
}
