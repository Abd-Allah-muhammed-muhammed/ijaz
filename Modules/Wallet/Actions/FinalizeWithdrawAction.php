<?php

namespace Modules\Wallet\Actions;

use Illuminate\Database\Eloquent\Model;
use Modules\Wallet\Models\WithdrawRequest;

class FinalizeWithdrawAction
{
    public function __construct(
        private readonly ReversePendingDebitAction $reversePendingDebitAction,
        private readonly DebitWalletAction $debitAction,
    ) {}

    public function handle(Model $owner, WithdrawRequest $request, bool $approved): void
    {
        $description = 'Wallet withdraw for '.get_class($request).' #'.$request->id;

        $this->reversePendingDebitAction->handle(
            $owner,
            (float) $request->amount,
            $request,
            $description,
        );

        if ($approved) {
            $this->debitAction->handle(
                $owner,
                (float) $request->amount,
                $request,
                $description,
            );
        }
    }
}
