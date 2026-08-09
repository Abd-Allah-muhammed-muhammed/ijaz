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

    /**
     * True when the owner already has a credit for this operation whose description
     * matches any of the given strings (used for idempotent registration-bonus grants).
     *
     * @param  list<string>  $descriptions
     */
    public function existsCreditForOperation(
        Model $owner,
        Model $operation,
        array $descriptions,
    ): bool;

    public function listForOwner(
        Model $owner,
        int $perPage = 15,
        ?string $dateFrom = null,
        ?string $dateTo = null,
    ): LengthAwarePaginator;

    /**
     * Wallet-scoped listing used by the dashboard, searchable by transaction or operation id.
     */
    public function paginateForWallet(
        Wallet $wallet,
        ?string $search = null,
        int $perPage = 25,
    ): LengthAwarePaginator;
}
