<?php

namespace Modules\Wallet\Services;

use App\Enums\OperationStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
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
        private readonly WalletService $walletService,
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

    /**
     * Admin dashboard approve/reject. Credits wallet only for Approved + Offline
     * (online credit is handled by HandleTopUpPaymentCompleted).
     */
    public function updateStatusForDashboard(
        TopUpRequest $topUpRequest,
        string $status,
        ?string $adminNotes,
        int $adminId,
    ): TopUpRequest {
        if ($topUpRequest->status !== OperationStatusEnum::Pending) {
            throw new WalletException('you can not update this top up request status');
        }

        return DB::transaction(function () use ($topUpRequest, $status, $adminNotes, $adminId): TopUpRequest {
            $topUpRequest->update([
                'status' => $status,
                'admin_notes' => $adminNotes,
                'admin_id' => $adminId,
            ]);

            if (
                $topUpRequest->status === OperationStatusEnum::Approved
                && $topUpRequest->payment_method->isOffline()
            ) {
                $this->walletService->credit(
                    owner: $topUpRequest->user,
                    amount: $topUpRequest->amount,
                    operation: $topUpRequest,
                    description: "Offline top-up approved #{$topUpRequest->id}",
                );
            }

            return $topUpRequest;
        });
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
