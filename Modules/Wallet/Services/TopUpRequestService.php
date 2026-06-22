<?php

namespace Modules\Wallet\Services;

use App\Enums\OperationStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Payment\DTOs\PaymentInitResult;
use Modules\Payment\Enums\PaymentStatusEnum;
use Modules\Payment\Services\PaymentService;
use Modules\Wallet\DTOs\CreateTopUpData;
use Modules\Wallet\Exceptions\WalletException;
use Modules\Wallet\Models\TopUpRequest;

class TopUpRequestService
{
    public function __construct(
        private readonly PaymentService $paymentService,
    ) {}

    /**
     * Create a top-up request (online or offline).
     * Caller must wrap in DB::transaction().
     *
     * @return array{topUpRequest: TopUpRequest, paymentResult: PaymentInitResult|null}
     */
    public function create(Model $owner, CreateTopUpData $data): array
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

        /** @var TopUpRequest $topUpRequest */
        $topUpRequest = $owner->topUpRequests()->create($attributes);

        if ($data->paymentMethod->isOnline()) {
            $result = $this->paymentService->initiate(
                owner: $owner,
                product: $topUpRequest,
                amount: $topUpRequest->amount,
                driver: $data->paymentDriver,
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

    public function cancel(TopUpRequest $topUpRequest): void
    {
        if (! $topUpRequest->status->isPending()) {
            throw new WalletException('Only pending top-up requests can be cancelled.');
        }

        $topUpRequest->delete();
    }

    public function listForOwner(Model $owner, int $perPage = 16): LengthAwarePaginator
    {
        return $owner->topUpRequests()
            ->latest()
            ->paginate($perPage);
    }

    public function listAll(int $perPage = 16): LengthAwarePaginator
    {
        return TopUpRequest::query()
            ->with('user')
            ->orderByRaw('status = ? DESC', [OperationStatusEnum::Pending->value])
            ->latest()
            ->paginate($perPage);
    }
}
