<?php

namespace Modules\Payment\Actions;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Modules\Payment\Contracts\Repositories\PaymentRepositoryInterface;

class GetAcceptedDailyTotalsSinceAction
{
    public function __construct(
        private readonly PaymentRepositoryInterface $repository,
    ) {}

    /**
     * @return Collection<string, float|int|string>
     */
    public function handle(CarbonInterface $since): Collection
    {
        return $this->repository->acceptedDailyTotalsSince($since);
    }
}
