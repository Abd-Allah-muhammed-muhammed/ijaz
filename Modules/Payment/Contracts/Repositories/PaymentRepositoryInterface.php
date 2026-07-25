<?php

namespace Modules\Payment\Contracts\Repositories;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

interface PaymentRepositoryInterface
{
    public function sumAcceptedAmount(): float|int|string;

    /**
     * @return Collection<string, float|int|string>
     */
    public function acceptedDailyTotalsSince(CarbonInterface $since): Collection;
}
