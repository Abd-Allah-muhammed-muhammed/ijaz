<?php

namespace Modules\Payout\Services;

use Illuminate\Database\Eloquent\Model;
use Modules\Payout\Actions\Payout\CreatePayoutRequestAction;
use Modules\Payout\DTOs\CreatePayoutRequestData;
use Modules\Payout\Models\PayoutRequest;

class PayoutService
{
    public function __construct(
        private readonly CreatePayoutRequestAction $createAction,
    ) {}

    public function createForOperation(Model $operation, Model $recipient, float $amount): PayoutRequest
    {
        return $this->createAction->handle(new CreatePayoutRequestData(
            operation: $operation,
            recipient: $recipient,
            amount: $amount,
        ));
    }
}
