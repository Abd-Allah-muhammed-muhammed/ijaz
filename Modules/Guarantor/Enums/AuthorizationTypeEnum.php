<?php

namespace Modules\Guarantor\Enums;

use App\Enums\Utilities\Collectable;
use App\Enums\Utilities\HasOperations;
use App\Enums\Utilities\Stringable;

enum AuthorizationTypeEnum: string
{
    use Collectable, HasOperations, Stringable;

    case Owner = 'owner';
    case Manager = 'manager';
    case Agency = 'agency';

    public function toString(): string
    {
        return __('guarantor.authorization_type.'.$this->value);
    }

    /**
     * @return array{value: string, label: string}
     */
    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'label' => $this->toString(),
        ];
    }
}
