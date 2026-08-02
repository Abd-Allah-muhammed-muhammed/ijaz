<?php

namespace Modules\Wallet\Actions\TopUp;

use App\Enums\OperationStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Modules\Payment\DTOs\PaymentInitResult;
use Modules\Payment\Enums\PaymentStatusEnum;
use Modules\Payment\Services\PaymentService;
use Modules\Wallet\Contracts\Repositories\TopUpRequestRepositoryInterface;
use Modules\Wallet\DTOs\CreateTopUpData;
use Modules\Wallet\Models\TopUpRequest;

class CreateTopUpRequestAction
{
    public function __construct(
        private readonly TopUpRequestRepositoryInterface $repository,
        private readonly PaymentService $paymentService,
    ) {}

    /**
     * Create a top-up request (online or offline).
     * Caller must wrap in DB::transaction().
     *
     * @return array{topUpRequest: TopUpRequest, paymentResult: PaymentInitResult|null}
     */
    public function handle(Model $owner, CreateTopUpData $data): array
    {
        $attributes = [
            'amount' => $data->amount,
            'payment_method' => $data->paymentMethod->value,
            'status' => OperationStatusEnum::Pending,
            'wallet_id' => $owner->wallet->id,
            'user_notes' => $data->userNotes,
            'transaction_image' => $data->transactionImage,
        ];

        if ($data->paymentMethod->isOnline()) {
            $attributes['payment_status'] = PaymentStatusEnum::Pending;
        }

        $topUpRequest = $this->repository->createForOwner($owner, $attributes);

        if ($data->paymentMethod->isOnline()) {
            // Server config is the only source of truth for which gateway is used.
            // Client-supplied payment_driver is ignored (kept on the DTO for BC only).
            $result = $this->paymentService->initiate(
                owner: $owner,
                product: $topUpRequest,
                amount: $topUpRequest->amount,
                driver: $this->paymentService->getDefaultDriver(),
            );

            return [
                'topUpRequest' => $topUpRequest,
                'paymentResult' => $result,
            ];
        }

        return [
            'topUpRequest' => $topUpRequest,
            'paymentResult' => null,
        ];
    }
}
