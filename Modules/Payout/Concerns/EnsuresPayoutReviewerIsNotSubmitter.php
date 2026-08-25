<?php

namespace Modules\Payout\Concerns;

use Modules\Payout\Exceptions\PayoutException;
use Modules\Payout\Models\PayoutRequest;

trait EnsuresPayoutReviewerIsNotSubmitter
{
    protected function ensurePayoutReviewerIsNotSubmitter(PayoutRequest $payoutRequest, int $adminId): void
    {
        if ($payoutRequest->submitted_by_admin_id === $adminId) {
            throw new PayoutException('payout.submitter_cannot_review');
        }
    }
}
