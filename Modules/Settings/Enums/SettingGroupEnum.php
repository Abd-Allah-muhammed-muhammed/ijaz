<?php

namespace Modules\Settings\Enums;

use App\Enums\Utilities\Collectable;
use App\Enums\Utilities\HasOperations;
use App\Enums\Utilities\Stringable;

enum SettingGroupEnum: string
{
    use Collectable, HasOperations, Stringable;

    case General = 'general';
    case Wallet = 'wallet';
    case Payment = 'payment';
    case Guarantor = 'guarantor';
    case Chat = 'chat';

    public function toArray(): array
    {
        return [
            'label' => $this->toString(),
            'color' => $this->color(),
            'value' => $this->value,
        ];
    }

    public function color(): string
    {
        return match ($this) {
            self::General => 'primary',
            self::Wallet => 'success',
            self::Payment => 'warning',
            self::Guarantor => 'info',
            self::Chat => 'secondary',
        };
    }
}
