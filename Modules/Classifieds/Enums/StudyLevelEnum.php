<?php

namespace Modules\Classifieds\Enums;

use App\Enums\Utilities\Collectable;
use App\Enums\Utilities\HasOperations;
use App\Enums\Utilities\HasTranslations;
use App\Enums\Utilities\Stringable;

enum StudyLevelEnum: string
{
    use Collectable, HasOperations, HasTranslations, Stringable;

    case DIPLOMA = 'diploma';
    case BACHELOR = 'bachelor';
    case MASTER = 'master';
    case PHD = 'phd';
    case CERTIFICATE = 'certificate';

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
        return trans('advisement.study_level.'.$this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::DIPLOMA => 'info',
            self::BACHELOR => 'primary',
            self::MASTER => 'success',
            self::PHD => 'warning',
            self::CERTIFICATE => 'secondary',
        };
    }

    protected function getTranslatableKey(): string
    {
        return 'advisement.study_level.'.$this->value;
    }
}
