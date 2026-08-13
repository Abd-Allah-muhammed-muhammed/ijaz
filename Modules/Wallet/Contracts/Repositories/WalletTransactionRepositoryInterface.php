<?php

namespace Modules\Wallet\Contracts\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
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

    /**
     * Paginate distinct (operation_id, operation_type) groups, with every ledger
     * row for each group on the page. Used to assemble lifecycle events without
     * splitting a group across pages.
     *
     * @return LengthAwarePaginator<int, Collection<int, WalletTransaction>>
     */
    public function listGroupedRowsForOwner(
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
