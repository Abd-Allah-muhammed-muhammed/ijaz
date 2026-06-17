<?php

namespace Modules\Guarantor\Repositories;

use Illuminate\Database\Eloquent\Model;
use Modules\Guarantor\Contracts\Repositories\StatusHistoryRepositoryInterface;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Guarantor\Models\GuarantorStatusHistory;

class StatusHistoryRepository implements StatusHistoryRepositoryInterface
{
    public function log(
        GuarantorRequest $request,
        Model $actor,
        ?string $fromStatus,
        string $toStatus,
        ?string $reason = null,
        ?string $notes = null,
    ): GuarantorStatusHistory {
        return $request->statusHistories()->create([
            'actor_id' => $actor->getKey(),
            'actor_type' => $actor::class,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'reason' => $reason,
            'notes' => $notes,
        ]);
    }
}
