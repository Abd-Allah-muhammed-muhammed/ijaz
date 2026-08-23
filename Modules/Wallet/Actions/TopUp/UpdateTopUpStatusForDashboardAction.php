<?php

namespace Modules\Wallet\Actions\TopUp;

use App\Enums\OperationStatusEnum;
use Illuminate\Support\Facades\DB;
use Modules\Payment\Enums\PaymentStatusEnum;
use Modules\Wallet\Contracts\Repositories\TopUpRequestRepositoryInterface;
use Modules\Wallet\Enums\WalletTransactionEntryKindEnum;
use Modules\Wallet\Exceptions\WalletException;
use Modules\Wallet\Models\TopUpRequest;
use Modules\Wallet\Services\WalletService;

class UpdateTopUpStatusForDashboardAction
{
    public function __construct(
        private readonly TopUpRequestRepositoryInterface $repository,
        private readonly WalletService $walletService,
    ) {}

    public function handle(
        TopUpRequest $topUpRequest,
        string $status,
        ?string $adminNotes,
        int $adminId,
    ): TopUpRequest {
        return DB::transaction(function () use ($topUpRequest, $status, $adminNotes, $adminId): TopUpRequest {
            // Re-check under row lock so concurrent admin approvals cannot double-credit.
            $topUpRequest = $this->repository->lockForUpdate($topUpRequest);

            if ($topUpRequest->payment_method->isOnline()) {
                throw new WalletException('wallet.online_top_up_payment_owned');
            }

            if ($topUpRequest->status !== OperationStatusEnum::Pending) {
                throw new WalletException('wallet.cannot_update_top_up_request_status');
            }

            if (
                $status === OperationStatusEnum::Approved->value
                && blank($topUpRequest->transaction_image)
            ) {
                throw new WalletException('wallet.top_up_proof_required');
            }

            $attributes = [
                'status' => $status,
                'admin_notes' => $adminNotes,
                'admin_id' => $adminId,
            ];

            // Mirror HandleTopUpPaymentCompleted / HandleTopUpPaymentFailed enum values.
            if ($status === OperationStatusEnum::Rejected->value) {
                $attributes['payment_status'] = PaymentStatusEnum::Rejected;
            } elseif ($status === OperationStatusEnum::Approved->value) {
                $attributes['payment_status'] = PaymentStatusEnum::Accepted;
            }

            $topUpRequest = $this->repository->update($topUpRequest, $attributes);

            if ($topUpRequest->status === OperationStatusEnum::Approved) {
                $this->walletService->credit(
                    owner: $topUpRequest->user,
                    amount: $topUpRequest->amount,
                    operation: $topUpRequest,
                    description: WalletTransactionEntryKindEnum::TopupCredited->translationKey(),
                    entryKind: WalletTransactionEntryKindEnum::TopupCredited,
                );
            }

            return $topUpRequest;
        });
    }
}
