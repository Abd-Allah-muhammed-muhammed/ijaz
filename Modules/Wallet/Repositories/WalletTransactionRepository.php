<?php

namespace Modules\Wallet\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Wallet\Contracts\Repositories\WalletTransactionRepositoryInterface;
use Modules\Wallet\DTOs\WalletTransactionData;
use Modules\Wallet\Models\Wallet;
use Modules\Wallet\Models\WalletTransaction;

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

    public function listForOwner(
        Model $owner,
        int $perPage = 15,
        ?string $dateFrom = null,
        ?string $dateTo = null,
    ): LengthAwarePaginator {
        return $owner->walletTransactions()
            ->when($dateFrom, fn ($query, $value) => $query->where('created_at', '>=', $value))
            ->when($dateTo, fn ($query, $value) => $query->where('created_at', '<=', $value))
            ->with(['operation'])
            ->latest()
            ->paginate($perPage);
    }
}
