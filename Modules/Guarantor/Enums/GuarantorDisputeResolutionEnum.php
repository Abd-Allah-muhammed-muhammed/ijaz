<?php

namespace Modules\Guarantor\Enums;

use App\Enums\Utilities\Collectable;
use App\Enums\Utilities\Stringable;

enum GuarantorDisputeResolutionEnum: string
{
    use Collectable, Stringable;

    case FullRequester = 'full_requester';
    case FullCounterparty = 'full_counterparty';
    case Escalate = 'escalate';
    case PercentageSplit = 'percentage_split';

    public function toString(): string
    {
        return __('guarantor.dispute_resolution.'.$this->value);
    }

    public function historyReason(): string
    {
        return match ($this) {
            self::FullRequester => 'dispute_resolved_full_requester',
            self::FullCounterparty => 'dispute_resolved_full_counterparty',
            self::Escalate => 'dispute_escalated_to_court',
            self::PercentageSplit => 'dispute_resolved_percentage_split',
        };
    }
}
