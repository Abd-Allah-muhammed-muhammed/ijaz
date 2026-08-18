<?php

namespace Modules\Payout\Actions\Payout;

use Modules\Payout\Contracts\Repositories\PayoutRequestRepositoryInterface;
use Modules\Payout\DTOs\CreatePayoutRequestData;
use Modules\Payout\Enums\PayoutStatusEnum;
use Modules\Payout\Models\PayoutRequest;

class CreatePayoutRequestAction
{
    public function __construct(
        private readonly PayoutRequestRepositoryInterface $repository,
    ) {}

    public function handle(CreatePayoutRequestData $data): PayoutRequest
    {
        return $this->repository->create([
            'operation_type' => $data->operation::class,
            'operation_id' => (string) $data->operation->getKey(),
            'recipient_type' => $data->recipient::class,
            'recipient_id' => $data->recipient->getKey(),
            'amount' => $data->amount,
            'status' => PayoutStatusEnum::Pending,
        ]);
    }
}
