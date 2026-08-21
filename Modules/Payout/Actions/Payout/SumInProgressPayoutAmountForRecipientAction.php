<?php

namespace Modules\Payout\Actions\Payout;

use Illuminate\Database\Eloquent\Model;
use Modules\Payout\Contracts\Repositories\PayoutRequestRepositoryInterface;

class SumInProgressPayoutAmountForRecipientAction
{
    public function __construct(
        private readonly PayoutRequestRepositoryInterface $repository,
    ) {}

    public function handle(Model $recipient): float
    {
        return $this->repository->sumInProgressAmountForRecipient($recipient);
    }
}
