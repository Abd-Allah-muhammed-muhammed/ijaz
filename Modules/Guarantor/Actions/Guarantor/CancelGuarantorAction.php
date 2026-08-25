<?php

namespace Modules\Guarantor\Actions\Guarantor;

use App\Models\Admin;
use Illuminate\Support\Facades\DB;
use Modules\Guarantor\Actions\Guarantor\UpdateGuarantorStatusAction as UpdateGuarantorStatus;
use Modules\Guarantor\DTOs\UpdateGuarantorStatusData;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Exceptions\GuarantorException;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Wallet\Services\WalletService;
use Throwable;

class CancelGuarantorAction
{
    public function __construct(
        private readonly UpdateGuarantorStatus $updateStatusAction,
        private readonly WalletService $walletService,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(
        GuarantorRequest $request,
        string $reason,
        ?string $notes,
        Admin $admin,
    ): void {
        DB::transaction(function () use ($request, $reason, $notes, $admin) {
            if ($request->status->isIn([
                GuarantorStatusEnum::Cancelled,
                GuarantorStatusEnum::Ended,
                GuarantorStatusEnum::Escalated,
            ])) {
                throw new GuarantorException('guarantor.status_transition_not_allowed', 422);
            }

            $this->updateStatusAction->handle(
                $request,
                new UpdateGuarantorStatusData(
                    status: GuarantorStatusEnum::Cancelled,
                    reason: $reason,
                    notes: $notes,
                ),
                $admin,
                'admin'
            );

            $this->reverseWalletHolds($request->fresh());
        });
    }

    private function reverseWalletHolds(GuarantorRequest $request): void
    {
        $request->loadMissing(['requester', 'counterparty', 'installments']);

        $operations = [
            $request,
            ...$request->installments->all(),
        ];

        $counterpartyHeld = $this->walletService->sumPendingDeltasForOperations(
            $request->counterparty,
            $operations,
        );
        if ($counterpartyHeld['pending_debit'] > 0) {
            $this->walletService->reversePendingDebit(
                $request->counterparty,
                $counterpartyHeld['pending_debit'],
                $request,
                "Guarantor#{$request->id} cancelled",
            );
        }

        $requesterHeld = $this->walletService->sumPendingDeltasForOperations(
            $request->requester,
            $operations,
        );
        if ($requesterHeld['pending_credit'] > 0) {
            $this->walletService->reversePendingCredit(
                $request->requester,
                $requesterHeld['pending_credit'],
                $request,
                "Guarantor#{$request->id} cancelled",
            );
        }
    }
}
