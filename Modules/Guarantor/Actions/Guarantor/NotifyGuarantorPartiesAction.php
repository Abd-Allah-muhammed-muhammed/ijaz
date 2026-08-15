<?php

namespace Modules\Guarantor\Actions\Guarantor;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notification;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Guarantor\Notifications\GuarantorCancelledNotification;
use Modules\Guarantor\Notifications\GuarantorEndedNotification;

class NotifyGuarantorPartiesAction
{
    /**
     * Load requester + counterparty and notify both.
     * Cancelled requests receive GuarantorCancelledNotification; all other
     * callers (the end path) receive GuarantorEndedNotification.
     */
    public function handle(GuarantorRequest $guarantorRequest): void
    {
        $guarantorRequest->load(['requester', 'counterparty']);

        collect([$guarantorRequest->requester, $guarantorRequest->counterparty])
            ->filter()
            ->each(function (Model $party) use ($guarantorRequest): void {
                $party->notify($this->notificationFor($guarantorRequest));
            });
    }

    private function notificationFor(GuarantorRequest $guarantorRequest): Notification
    {
        if ($guarantorRequest->status->is(GuarantorStatusEnum::Cancelled)) {
            return new GuarantorCancelledNotification($guarantorRequest);
        }

        return new GuarantorEndedNotification($guarantorRequest);
    }
}
