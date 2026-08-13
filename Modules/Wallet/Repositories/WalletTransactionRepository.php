<?php

namespace Modules\Wallet\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Wallet\Contracts\Repositories\WalletTransactionRepositoryInterface;
use Modules\Wallet\DTOs\WalletTransactionData;
use Modules\Wallet\Models\Wallet;
use Modules\Wallet\Models\WalletTransaction;
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

    /**
     * Paginate lifecycle groups (operation_id + operation_type), then load every
     * ledger row for those groups so a group is never split across pages.
     *
     * @return LengthAwarePaginator<int, Collection<int, WalletTransaction>>
     */
    public function listGroupedRowsForOwner(
        Model $owner,
        int $perPage = 15,
        ?string $dateFrom = null,
        ?string $dateTo = null,
    ): LengthAwarePaginator {
        $groupsQuery = $owner->walletTransactions()
            ->select('operation_id', 'operation_type')
            ->selectRaw('MAX(created_at) as group_created_at')
            ->when($dateFrom, fn (Builder $query, string $value) => $query->where('created_at', '>=', $value))
            ->when($dateTo, fn (Builder $query, string $value) => $query->where('created_at', '<=', $value))
            ->groupBy('operation_id', 'operation_type');

        $total = (int) DB::query()
            ->fromSub($groupsQuery->clone()->toBase(), 'lifecycle_groups')
            ->count();

        $page = LengthAwarePaginator::resolveCurrentPage();

        $keys = $groupsQuery
            ->orderByDesc('group_created_at')
            ->forPage($page, $perPage)
            ->get();

        if ($keys->isEmpty()) {
            return new LengthAwarePaginator(collect(), $total, $perPage, $page, [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
            ]);
        }

        $rows = $owner->walletTransactions()
            ->where(function (Builder $query) use ($keys): void {
                foreach ($keys as $key) {
                    $query->orWhere(function (Builder $inner) use ($key): void {
                        $inner->where('operation_id', $key->operation_id)
                            ->where('operation_type', $key->operation_type);
                    });
                }
            })
            ->orderBy('created_at')
            ->get()
            ->groupBy(fn (WalletTransaction $row): string => $row->operation_type.'|'.$row->operation_id);

        $grouped = $keys
            ->map(fn ($key) => $rows->get($key->operation_type.'|'.$key->operation_id, collect()))
            ->values();

        return new LengthAwarePaginator($grouped, $total, $perPage, $page, [
            'path' => LengthAwarePaginator::resolveCurrentPath(),
        ]);
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
}
