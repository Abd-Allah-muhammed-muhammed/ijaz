<?php

namespace Modules\Wallet\Actions;

use Illuminate\Database\Eloquent\Model;
use Modules\Wallet\Actions\DebitWalletAction as WithdrawDebitWalletAction;
use Modules\Wallet\Actions\RecordWithdrawRejectedAction as WithdrawRecordRejectedAction;
use Modules\Wallet\Actions\ReversePendingDebitAction as WithdrawReversePendingDebitAction;
use Modules\Wallet\Enums\WalletTransactionEntryKindEnum;
use Modules\Wallet\Models\WithdrawRequest;

class FinalizeWithdrawAction
{
    public function __construct(
        private readonly WithdrawReversePendingDebitAction $reversePendingDebitAction,
        private readonly WithdrawDebitWalletAction $debitAction,
        private readonly WithdrawRecordRejectedAction $recordWithdrawRejectedAction,
    ) {}

    public function handle(Model $owner, WithdrawRequest $request, bool $approved): void
    {
        $description = 'Wallet withdraw for '.get_class($request).' #'.$request->id;

        $this->reversePendingDebitAction->handle(
            $owner,
            (float) $request->amount,
            $request,
            $description,
            WalletTransactionEntryKindEnum::WithdrawHoldReleased,
        );

        if ($approved) {
            $this->debitAction->handle(
                $owner,
                (float) $request->amount,
                $request,
                $description,
                WalletTransactionEntryKindEnum::WithdrawApproved,
            );

            return;
        }

        $this->recordWithdrawRejectedAction->handle($owner, $request, $description);
    }
}
