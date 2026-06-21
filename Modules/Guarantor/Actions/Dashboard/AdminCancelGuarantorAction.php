<?php

namespace Modules\Guarantor\Actions\Dashboard;

use App\Models\Admin;
use Illuminate\Support\Facades\DB;
use Modules\Guarantor\Actions\Guarantor\UpdateGuarantorStatusAction;
use Modules\Guarantor\DTOs\UpdateGuarantorStatusData;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Exceptions\GuarantorException;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Wallet\Services\WalletService;
use Throwable;

class AdminCancelGuarantorAction
{
    public function __construct(
        private readonly UpdateGuarantorStatusAction $updateStatusAction,
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
                GuarantorStatusEnum::Refunded,
                GuarantorStatusEnum::Ended,
            ])) {
                throw new GuarantorException('guarantor.status_transition_not_allowed', 422);
            }

            $hadPayment = $request->status->isIn([
                GuarantorStatusEnum::InProgress,
                GuarantorStatusEnum::Overdue,
            ]);

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

            if ($hadPayment) {
                $this->reverseWalletHolds($request->fresh());
            }
        });
    }

    private function reverseWalletHolds(GuarantorRequest $request): void
    {
        $request->loadMissing(['requester', 'counterparty']);

        $total = (float) $request->amount + (float) $request->fees;

        $counterpartyWallet = $request->counterparty->wallet()->lockForUpdate()->firstOrCreate();
        if ((float) $counterpartyWallet->pending_debit >= $total) {
            $this->walletService->reversePendingDebit(
                $request->counterparty,
                $total,
                $request,
                "Guarantor#{$request->id} cancelled",
            );
        }

        $requesterWallet = $request->requester->wallet()->lockForUpdate()->firstOrCreate();
        if ((float) $requesterWallet->pending_credit >= $total) {
            $this->walletService->reversePendingCredit(
                $request->requester,
                $total,
                $request,
                "Guarantor#{$request->id} cancelled",
            );
        }
    }
}
