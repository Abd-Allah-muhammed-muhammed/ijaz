<?php

namespace Modules\Payment\Actions;

use Modules\Payment\Contracts\Repositories\PaymentRepositoryInterface;

class SumAcceptedPaymentsAction
{
    public function __construct(
        private readonly PaymentRepositoryInterface $repository,
    ) {}

    public function handle(): float|int|string
    {
        return $this->repository->sumAcceptedAmount();
    }
}
