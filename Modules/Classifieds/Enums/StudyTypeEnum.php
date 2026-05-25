<?php

namespace Modules\Classifieds\Enums;

use App\Enums\Utilities\Collectable;
use App\Enums\Utilities\HasOperations;
use App\Enums\Utilities\HasTranslations;
use App\Enums\Utilities\Stringable;

enum StudyTypeEnum: string
{
    use Collectable, HasOperations, HasTranslations, Stringable;

    case ONSITE = 'onsite';
    case ONLINE = 'online';
    case HYBRID = 'hybrid';

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
        return trans('advisement.study_type.'.$this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::ONSITE => 'success',
            self::ONLINE => 'info',
            self::HYBRID => 'warning',
        };
    }

    protected function getTranslatableKey(): string
    {
        return 'advisement.study_type.'.$this->value;
    }
}
