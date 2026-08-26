<?php

namespace Modules\Guarantor\Actions\Guarantor;

use Modules\Guarantor\Contracts\Repositories\InstallmentRepositoryInterface;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Wallet\Services\WalletService;

/**
 * Reverses THIS guarantor's scoped wallet holds the same way
 * {@see CancelGuarantorAction} does (not the owner's entire pending_*).
 */
class ReverseGuarantorWalletHoldsAction
{
    public function __construct(
        private readonly WalletService $walletService,
        private readonly InstallmentRepositoryInterface $installmentRepository,
    ) {}

    public function handle(GuarantorRequest $request, string $descriptionLabel = 'cancelled'): void
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
                "Guarantor#{$request->id} {$descriptionLabel}",
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
                "Guarantor#{$request->id} {$descriptionLabel}",
            );
        }

        $this->installmentRepository->markPaidAsReversedForRequest($request);
    }
}
