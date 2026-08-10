<?php

namespace Modules\Classifieds\Actions;

use Illuminate\Database\Eloquent\Model;
use Modules\Classifieds\Enums\AdvisementStatusEnum;
use Modules\Classifieds\Notifications\AdvisementStatusChangedNotification;

/**
 * Shared notify step for all four Update*AdvisementStatusForDashboardAction classes.
 */
final class NotifyAdvisementOwnerOfStatusChangeAction
{
    /**
     * @param  'car'|'property'|'electronic'|'institute'  $advisementKind
     */
    public function handle(
        Model $advisement,
        AdvisementStatusEnum $previous,
        AdvisementStatusEnum $next,
        string $advisementKind,
    ): void {
        if ($previous === $next) {
            return;
        }

        if (! AdvisementStatusChangedNotification::shouldNotify($next->value)) {
            return;
        }

        $owner = $advisement->user;

        if ($owner === null) {
            return;
        }

        $owner->notify(new AdvisementStatusChangedNotification(
            advisement: $advisement,
            status: $next->value,
            advisementKind: $advisementKind,
        ));
    }
}
