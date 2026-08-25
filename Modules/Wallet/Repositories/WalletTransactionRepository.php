<?php

namespace Modules\Wallet\Repositories;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Wallet\Contracts\Repositories\WalletTransactionRepositoryInterface;
use Modules\Wallet\DTOs\WalletTransactionData;
use Modules\Wallet\Enums\WalletTransactionEntryKindEnum;
use Modules\Wallet\Models\Wallet;
use Modules\Wallet\Models\WalletTransaction;
use Modules\Wallet\Models\WithdrawRequest;
use Modules\Wallet\Support\WalletSearch;
use Modules\Wallet\Support\WalletTransactionQueryFilters;

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
            ->tap(fn (Builder $query) => WalletTransactionQueryFilters::excludeInternalWithdrawRows($query))
            ->when($dateFrom, fn ($query, $value) => $query->where('created_at', '>=', $value))
            ->when($dateTo, fn ($query, $value) => $query->where('created_at', '<=', $value))
            ->tap(fn (Builder $query) => WalletTransactionQueryFilters::withOperationForStatus($query))
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

    public function latestForWallet(Wallet $wallet, int $limit = 5): Collection
    {
        return $wallet->transactions()
            ->tap(fn (Builder $query) => WalletTransactionQueryFilters::excludeInternalWithdrawRows($query))
            ->tap(fn (Builder $query) => WalletTransactionQueryFilters::withOperationForStatus($query))
            ->latest()
            ->limit($limit)
            ->get();
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

    public function sumPendingDeltasForOperations(Model $owner, array $operations): array
    {
        if ($operations === []) {
            return ['pending_credit' => 0.0, 'pending_debit' => 0.0];
        }

        $grouped = [];
        foreach ($operations as $operation) {
            $type = $operation::class;
            $grouped[$type][] = (string) $operation->getKey();
        }

        $row = $owner->walletTransactions()
            ->where(function (Builder $query) use ($grouped): void {
                foreach ($grouped as $type => $ids) {
                    $query->orWhere(function (Builder $query) use ($type, $ids): void {
                        $query->where('operation_type', $type)
                            ->whereIn('operation_id', $ids);
                    });
                }
            })
            ->selectRaw('COALESCE(SUM(pending_credit), 0) as pending_credit_sum, COALESCE(SUM(pending_debit), 0) as pending_debit_sum')
            ->first();

        return [
            'pending_credit' => max(0.0, (float) ($row?->pending_credit_sum ?? 0)),
            'pending_debit' => max(0.0, (float) ($row?->pending_debit_sum ?? 0)),
        ];
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
