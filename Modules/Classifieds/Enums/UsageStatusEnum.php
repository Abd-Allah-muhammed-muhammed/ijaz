<?php

namespace Modules\Classifieds\Enums;

enum UsageStatusEnum: string
{
    case NEW = 'new';
    case USED = 'used';
    case NOT_SPECIFIED = 'not_specified';

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
        return trans('advisement.usage_status.'.$this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::NEW => 'success',
            self::USED => 'warning',
            self::NOT_SPECIFIED => 'secondary',
        };
    }
}
