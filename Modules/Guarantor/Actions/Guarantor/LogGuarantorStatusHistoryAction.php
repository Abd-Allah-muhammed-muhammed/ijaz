<?php

namespace Modules\Guarantor\Actions\Guarantor;

use Illuminate\Database\Eloquent\Model;
use Modules\Guarantor\Contracts\Repositories\StatusHistoryRepositoryInterface;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Guarantor\Models\GuarantorStatusHistory;

class LogGuarantorStatusHistoryAction
{
    public function __construct(
        private readonly StatusHistoryRepositoryInterface $statusHistory,
    ) {}

    public function handle(
        GuarantorRequest $request,
        Model $actor,
        ?string $fromStatus,
        string $toStatus,
        ?string $reason = null,
        ?string $notes = null,
    ): GuarantorStatusHistory {
        return $this->statusHistory->log(
            $request,
            $actor,
            $fromStatus,
            $toStatus,
            $reason,
            $notes,
        );
    }
}
