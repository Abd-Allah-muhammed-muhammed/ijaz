<?php

namespace Modules\Classifieds\Enums;

use App\Enums\Utilities\Collectable;
use App\Enums\Utilities\HasOperations;
use App\Enums\Utilities\HasTranslations;
use App\Enums\Utilities\Stringable;

enum InstituteTypeEnum: string
{
    use Collectable, HasOperations, HasTranslations, Stringable;

    case INSTITUTE = 'institute';
    case UNIVERSITY = 'university';

    /**
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
        return trans('advisement.institute_type.'.$this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::INSTITUTE => 'info',
            self::UNIVERSITY => 'primary',
        };
    }

    protected function getTranslatableKey(): string
    {
        return 'advisement.institute_type.'.$this->value;
    }
}
