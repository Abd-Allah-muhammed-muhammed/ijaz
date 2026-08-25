<?php

namespace Modules\Guarantor\Actions\Guarantor;

use Modules\Guarantor\Actions\Installment\ReleaseInstallmentAction;
use Modules\Guarantor\Enums\GuarantorTypeEnum;
use Modules\Guarantor\Enums\InstallmentStatusEnum;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Wallet\Services\WalletService;

/**
 * Releases held funds the same way {@see EndGuarantorAction} does —
 * Individual: pending_credit → balance (net) + clear counterparty pending_debit;
 * Company: release the latest paid installment.
 */
class ReleaseGuarantorWalletHoldsAction
{
    public function __construct(
        private readonly ReleaseInstallmentAction $releaseInstallmentAction,
        private readonly WalletService $walletService,
    ) {}

    public function handle(GuarantorRequest $request): void
    {
        if ($request->type === GuarantorTypeEnum::Individual) {
            $this->releaseIndividualWallets($request);

            return;
        }

        $this->releaseLastPaidInstallment($request);
    }

    private function releaseIndividualWallets(GuarantorRequest $request): void
    {
        $request->loadMissing(['requester', 'counterparty']);

        $grossAmount = (float) $request->amount + (float) $request->fees;
        $netAmount = (float) $request->amount;

        $requesterWallet = $request->requester->wallet()->lockForUpdate()->firstOrCreate();
        if ((float) $requesterWallet->pending_credit > 0) {
            $this->walletService->releasePendingCreditToBalance(
                $request->requester,
                $grossAmount,
                $netAmount,
                $request,
                "Guarantor#{$request->id} ended — funds released",
            );
        }

        $counterpartyWallet = $request->counterparty->wallet()->lockForUpdate()->firstOrCreate();
        if ((float) $counterpartyWallet->pending_debit > 0) {
            $this->walletService->reversePendingDebit(
                $request->counterparty,
                $grossAmount,
                $request,
                "Guarantor#{$request->id} ended — pending released",
            );
        }
    }

    private function releaseLastPaidInstallment(GuarantorRequest $request): void
    {
        $installment = $request->installments()
            ->where('status', InstallmentStatusEnum::Paid)
            ->orderByDesc('order')
            ->first();

        if ($installment !== null) {
            $this->releaseInstallmentAction->handle($installment, 'end');
        }
    }
}
