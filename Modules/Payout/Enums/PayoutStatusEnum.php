<?php

namespace Modules\Payout\Enums;

use App\Enums\Utilities\Collectable;
use App\Enums\Utilities\HasOperations;
use App\Enums\Utilities\Stringable;

enum PayoutStatusEnum: string
{
    use Collectable, HasOperations, Stringable;

    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';

    /**
     * @return array{label: string, color: string, value: string}
     */
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
            self::Pending => 'primary',
            self::Processing => 'info',
            self::Completed => 'success',
            self::Failed => 'danger',
        };
    }
}
