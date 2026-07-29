<?php

namespace App\Actions\Provider;

use App\Models\Provider;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Wallet\Services\WalletService;

class ListProviderWalletTransactionsAction
{
    public function __construct(
        private readonly WalletService $walletService,
    ) {}

    public function handle(Provider $provider, ?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        return $this->walletService->listTransactionsForWallet($provider->wallet, $search, $perPage);
    }
}
