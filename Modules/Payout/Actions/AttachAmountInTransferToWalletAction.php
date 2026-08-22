<?php

namespace Modules\Payout\Actions;

use Illuminate\Database\Eloquent\Model;
use Modules\Wallet\Models\Wallet;

class AttachAmountInTransferToWalletAction
{
    public function __construct(
        private readonly SumInProgressPayoutAmountForRecipientAction $sumInProgressAmountForRecipientAction,
    ) {}

    public function handle(Model $recipient): void
    {
        /** @var Wallet $wallet */
        $wallet = $recipient->wallet;

        $wallet->setAttribute(
            'amount_in_transfer',
            $this->sumInProgressAmountForRecipientAction->handle($recipient),
        );
    }
}
