<?php

namespace Modules\Wallet\Repositories;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Wallet\Contracts\Repositories\WalletTransactionRepositoryInterface;
use Modules\Wallet\DTOs\WalletTransactionData;
use Modules\Wallet\Enums\WalletTransactionEntryKindEnum;
use Modules\Wallet\Models\Wallet;
use Modules\Wallet\Models\WalletTransaction;
use Modules\Wallet\Models\WithdrawRequest;
use Modules\Wallet\Support\WalletSearch;

class WalletTransactionRepository implements WalletTransactionRepositoryInterface
{
    public function create(Wallet $wallet, Model $owner, WalletTransactionData $data): WalletTransaction
    {
        return $owner->walletTransactions()->create([
            'wallet_id' => $wallet->id,
            'credit' => $data->credit,
            'debit' => $data->debit,
            'balance_before' => $data->balance_before,
            'balance_after' => $data->balance_after,
            'pending_credit' => $data->pending_credit,
            'pending_debit' => $data->pending_debit,
            'description' => $data->description,
            'operation_type' => $data->operation_type,
            'operation_id' => $data->operation_id,
            'payment_id' => $data->payment_id,
            'entry_kind' => $data->entry_kind,
        ]);
    }

    public function existsCreditForOperation(
        Model $owner,
        Model $operation,
        array $descriptions,
    ): bool {
        if ($descriptions === []) {
            return false;
        }

        return $owner->walletTransactions()
            ->where('operation_type', $operation::class)
            ->where('operation_id', (string) $operation->getKey())
            ->where('credit', '>', 0)
            ->whereIn('description', $descriptions)
            ->exists();
    }

    public function listForOwner(
        Model $owner,
        int $perPage = 15,
        ?string $dateFrom = null,
        ?string $dateTo = null,
    ): LengthAwarePaginator {
        return $owner->walletTransactions()
            ->tap(fn (Builder $query) => $this->excludeInternalWithdrawRows($query))
            ->when($dateFrom, fn ($query, $value) => $query->where('created_at', '>=', $value))
            ->when($dateTo, fn ($query, $value) => $query->where('created_at', '<=', $value))
            ->with(['operation' => function (MorphTo $morphTo): void {
                $morphTo->morphWith([
                    WithdrawRequest::class => ['payoutRequest'],
                ]);
            }])
            ->latest()
            ->paginate($perPage);
    }

    public function paginateForWallet(
        Wallet $wallet,
        ?string $search = null,
        int $perPage = 25,
    ): LengthAwarePaginator {
        $search = WalletSearch::normalize($search);

        return $wallet->transactions()
            ->latest()
            ->when($search, function (Builder $query) use ($search): void {
                $query->where(function (Builder $q) use ($search): void {
                    $q->where('id', 'like', "%{$search}%")
                        ->orWhere('operation_id', 'like', "%{$search}%")
                        ->orWhere('payment_id', 'like', "%{$search}%");
                });
            })
            ->paginate($perPage)
            ->withQueryString();
    }

    public function latestForWallet(Wallet $wallet, int $limit = 2): Collection
    {
        return $wallet->transactions()
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Hide hold-release rows, and hide the original withdraw request once a
     * terminal sibling (approved / rejected / cancelled) exists for the group.
     */
    private function excludeInternalWithdrawRows(Builder $query): void
    {
        $table = $query->getModel()->getTable();

        $query
            ->where(function (Builder $visible) use ($table): void {
                $visible->whereNull($table.'.entry_kind')
                    ->orWhere($table.'.entry_kind', '!=', WalletTransactionEntryKindEnum::WithdrawHoldReleased);
            })
            ->where(function (Builder $requested) use ($table): void {
                $requested->whereNull($table.'.entry_kind')
                    ->orWhere($table.'.entry_kind', '!=', WalletTransactionEntryKindEnum::WithdrawRequested)
                    ->orWhereNotExists(function ($siblings) use ($table): void {
                        $siblings->selectRaw('1')
                            ->from($table.' as entry_kind_siblings')
                            ->whereColumn('entry_kind_siblings.operation_id', $table.'.operation_id')
                            ->whereColumn('entry_kind_siblings.operation_type', $table.'.operation_type')
                            ->whereIn('entry_kind_siblings.entry_kind', [
                                WalletTransactionEntryKindEnum::WithdrawApproved,
                                WalletTransactionEntryKindEnum::WithdrawRejected,
                                WalletTransactionEntryKindEnum::WithdrawCancelled,
                            ]);
                    });
            });
    }

    public function countUnstamped(Closure $constraints): int
    {
        return WalletTransaction::query()
            ->where(function (Builder $query) use ($constraints): void {
                $query->whereNull('entry_kind');
                $constraints($query);
            })
            ->count();
    }

    public function chunkUnstampedById(Closure $constraints, int $chunkSize, Closure $callback): void
    {
        WalletTransaction::query()
            ->where(function (Builder $query) use ($constraints): void {
                $query->whereNull('entry_kind');
                $constraints($query);
            })
            ->chunkById($chunkSize, $callback);
    }

    public function stampEntryKind(array $ids, WalletTransactionEntryKindEnum $kind): int
    {
        if ($ids === []) {
            return 0;
        }

        return WalletTransaction::query()
            ->whereIn('id', $ids)
            ->whereNull('entry_kind')
            ->update([
                'entry_kind' => $kind->value,
                'description' => $kind->translationKey(),
            ]);
    }

    public function countStaleDescriptions(WalletTransactionEntryKindEnum $kind): int
    {
        return WalletTransaction::query()
            ->where(function (Builder $query) use ($kind): void {
                $this->constrainStaleDescription($query, $kind);
            })
            ->count();
    }

    public function chunkStaleDescriptionsById(WalletTransactionEntryKindEnum $kind, int $chunkSize, Closure $callback): void
    {
        WalletTransaction::query()
            ->where(function (Builder $query) use ($kind): void {
                $this->constrainStaleDescription($query, $kind);
            })
            ->chunkById($chunkSize, $callback);
    }

    public function stampDescriptionKey(array $ids, WalletTransactionEntryKindEnum $kind): int
    {
        if ($ids === []) {
            return 0;
        }

        $key = $kind->translationKey();

        return WalletTransaction::query()
            ->whereIn('id', $ids)
            ->where('entry_kind', $kind->value)
            ->where(function (Builder $query) use ($key): void {
                $query->whereNull('description')
                    ->orWhere('description', '!=', $key);
            })
            ->update(['description' => $key]);
    }

    public function withdrawOperationIdsWithDebit(): Collection
    {
        return WalletTransaction::query()
            ->where('operation_type', WithdrawRequest::class)
            ->where('debit', '>', 0)
            ->pluck('operation_id');
    }

    private function constrainStaleDescription(Builder $query, WalletTransactionEntryKindEnum $kind): void
    {
        $key = $kind->translationKey();

        $query->where('entry_kind', $kind->value)
            ->where(function (Builder $query) use ($key): void {
                $query->whereNull('description')
                    ->orWhere('description', '!=', $key);
            });
    }
}
