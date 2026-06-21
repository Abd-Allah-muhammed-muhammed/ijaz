<?php

namespace Modules\Payment\Enums;

use App\Enums\Utilities\HasOperations;

enum PaymentMethodEnum: string
{
    use HasOperations;
    case Offline = 'offline';
    case Online = 'online';

    public function toArray(): array
    {
        return [
            'label' => $this->toString(),
            'value' => $this->value,
        ];
    }

    public function toString(): string
    {
        return trans(strtolower($this->value));
    }

    public function isOffline(): bool
    {
        return $this->is(self::Offline);
    }

    public function isOnline(): bool
    {
        return $this->is(self::Online);
    }
}
