<?php

namespace Modules\Wallet\Contracts\Repositories;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Wallet\DTOs\WalletTransactionData;
use Modules\Wallet\Enums\WalletTransactionEntryKindEnum;
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

    /**
     * @param  Closure(Builder): void  $constraints
     */
    public function countUnstamped(Closure $constraints): int;

    /**
     * @param  Closure(Builder): void  $constraints
     * @param  Closure(Collection<int, WalletTransaction>): void  $callback
     */
    public function chunkUnstampedById(Closure $constraints, int $chunkSize, Closure $callback): void;

    /**
     * @param  list<string>  $ids
     */
    public function stampEntryKind(array $ids, WalletTransactionEntryKindEnum $kind): int;

    public function countStaleDescriptions(WalletTransactionEntryKindEnum $kind): int;

    /**
     * @param  Closure(Collection<int, WalletTransaction>): void  $callback
     */
    public function chunkStaleDescriptionsById(WalletTransactionEntryKindEnum $kind, int $chunkSize, Closure $callback): void;

    /**
     * @param  list<string>  $ids
     */
    public function stampDescriptionKey(array $ids, WalletTransactionEntryKindEnum $kind): int;

    /**
     * @return Collection<int, string>
     */
    public function withdrawOperationIdsWithDebit(): Collection;
}
