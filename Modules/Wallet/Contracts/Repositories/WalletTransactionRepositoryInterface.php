<?php

namespace Modules\Wallet\Contracts\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Wallet\DTOs\WalletTransactionData;
use Modules\Wallet\Models\Wallet;
use Modules\Wallet\Models\WalletTransaction;

interface WalletTransactionRepositoryInterface
{
    public function create(Wallet $wallet, Model $owner, WalletTransactionData $data): WalletTransaction;

    public function listForOwner(
        Model $owner,
        int $perPage = 15,
        ?string $dateFrom = null,
        ?string $dateTo = null,
    ): LengthAwarePaginator;
}
