<?php

namespace Modules\Guarantor\Contracts\Repositories;

use Illuminate\Database\Eloquent\Model;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Guarantor\Models\GuarantorStatusHistory;

interface StatusHistoryRepositoryInterface
{
    public function log(
        GuarantorRequest $request,
        Model $actor,
        ?string $fromStatus,
        string $toStatus,
        ?string $reason = null,
        ?string $notes = null,
    ): GuarantorStatusHistory;
}
