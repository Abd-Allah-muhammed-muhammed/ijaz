<?php

namespace Modules\Settings\Enums;

use App\Enums\Utilities\Collectable;
use App\Enums\Utilities\HasOperations;
use App\Enums\Utilities\Stringable;

enum SettingTypeEnum: string
{
    use Collectable, HasOperations, Stringable;

    case Text = 'text';
    case Textarea = 'textarea';

    /**
     * @return array{label: string, value: string}
     */
    public function toArray(): array
    {
        return [
            'label' => $this->toString(),
            'value' => $this->value,
        ];
    }
}
