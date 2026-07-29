<?php

namespace Modules\Guarantor\Actions\Guarantor;

use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Guarantor\Notifications\GuarantorEndedNotification;

class NotifyGuarantorPartiesAction
{
    /**
     * Load requester + counterparty and notify both with GuarantorEndedNotification.
     */
    public function handle(GuarantorRequest $guarantorRequest): void
    {
        $guarantorRequest->load(['requester', 'counterparty']);

        collect([$guarantorRequest->requester, $guarantorRequest->counterparty])
            ->each->notify(new GuarantorEndedNotification($guarantorRequest));
    }
}
