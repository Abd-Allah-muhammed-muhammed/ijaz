<?php

namespace Modules\Classifieds\Enums;

use App\Enums\Utilities\Collectable;
use App\Enums\Utilities\HasOperations;
use App\Enums\Utilities\HasTranslations;
use App\Enums\Utilities\Stringable;

enum ElectronicConditionEnum: string
{
    use Collectable, HasOperations, HasTranslations, Stringable;

    case NEW = 'new';
    case USED = 'used';
    case LESS_THAN_YEAR = 'less_than_year';

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
        return trans('advisement.condition.'.$this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::NEW => 'success',
            self::USED => 'warning',
            self::LESS_THAN_YEAR => 'info',
        };
    }

    protected function getTranslatableKey(): string
    {
        return 'advisement.condition.'.$this->value;
    }
}
