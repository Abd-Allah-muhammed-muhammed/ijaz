<?php

namespace Modules\Guarantor\Actions\Guarantor;

/**
 * Snapshots the Guarantor platform fee at request creation.
 *
 * Formula: round(contractAmount * guarantee_fee_percent / 100, 2)
 * — same half-up round() convention used by installment fee proration.
 *
 * Existing GuarantorRequest rows are never recalculated; only new creates
 * read the current setting.
 */
class CalculateGuarantorFeesAction
{
    /**
     * Placeholder default matches SettingsSeeder — confirm the live business
     * rate via the Settings dashboard before launch.
     */
    public const DEFAULT_PERCENT = 2.5;

    public function handle(float $contractAmount): float
    {
        $percent = (float) app('settings')->get('guarantee_fee_percent', self::DEFAULT_PERCENT);

        return round($contractAmount * $percent / 100, 2);
    }
}
