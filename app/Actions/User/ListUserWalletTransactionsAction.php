<?php

namespace App\Actions\User;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Wallet\Services\WalletService;

class ListUserWalletTransactionsAction
{
    public function __construct(
        private readonly WalletService $walletService,
    ) {}

    public function handle(User $user, ?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        return $this->walletService->listTransactionsForWallet($user->wallet, $search, $perPage);
    }
}
