<?php

namespace App\Enums;

use App\Contracts\Selects\IReactSelect;
use App\Enums\Utilities\Collectable;
use App\Enums\Utilities\HasOperations;
use App\Enums\Utilities\Stringable;

enum CategoryFeesTypeEnum: string implements IReactSelect
{
    use Collectable, HasOperations, Stringable;
    case INHERITED = 'inherited';
    case FIXED = 'fixed';
    case PERCENTAGE = 'percentage';

    public function getLabel(): string
    {
        return $this->toString();
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function toArray(): array
    {
        return [
            'label' => $this->getLabel(),
            'value' => $this->getValue(),
        ];
    }
}
