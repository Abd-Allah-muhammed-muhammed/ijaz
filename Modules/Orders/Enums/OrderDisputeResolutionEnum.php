<?php

namespace Modules\Orders\Enums;

use App\Enums\Utilities\Collectable;
use App\Enums\Utilities\Stringable;

enum OrderDisputeResolutionEnum: string
{
    use Collectable, Stringable;

    case FullUser = 'full_user';
    case FullProvider = 'full_provider';
    case Escalate = 'escalate';
    case PercentageSplit = 'percentage_split';

    public function toString(): string
    {
        return __('orders.dispute_resolution.'.$this->value);
    }

    public function historyReason(): string
    {
        return match ($this) {
            self::FullUser => 'dispute_resolved_full_user',
            self::FullProvider => 'dispute_resolved_full_provider',
            self::Escalate => 'dispute_escalated_to_court',
            self::PercentageSplit => 'dispute_resolved_percentage_split',
        };
    }
}
