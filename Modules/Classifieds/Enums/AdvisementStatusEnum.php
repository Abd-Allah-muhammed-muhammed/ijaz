<?php

namespace Modules\Classifieds\Enums;

enum AdvisementStatusEnum: string
{
    case PUBLISHED = 'published';
    case PENDING = 'pending';
    case REJECTED = 'rejected';
    case CLOSED = 'closed';

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
        return __('advisement.status.'.$this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::PUBLISHED => 'success',
            self::PENDING => 'warning',
            self::REJECTED => 'danger',
            self::CLOSED => 'secondary',
        };
    }
}
