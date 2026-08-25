<?php

namespace Modules\Wallet\Repositories;

use App\Enums\OperationStatusEnum;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Wallet\Contracts\Repositories\WithdrawRequestRepositoryInterface;
use Modules\Wallet\Models\WithdrawRequest;
use Modules\Wallet\Support\WalletSearch;

class WithdrawRequestRepository implements WithdrawRequestRepositoryInterface
{
    public function createForOwner(Model $owner, array $attributes): WithdrawRequest
    {
        /** @var WithdrawRequest $withdrawRequest */
        $withdrawRequest = $owner->withdrawRequests()->create($attributes);

        return $withdrawRequest;
    }

    public function lockForUpdate(WithdrawRequest $withdrawRequest): WithdrawRequest
    {
        /** @var WithdrawRequest $locked */
        $locked = WithdrawRequest::query()
            ->whereKey($withdrawRequest->getKey())
            ->lockForUpdate()
            ->firstOrFail();

        return $locked;
    }

    public function update(WithdrawRequest $withdrawRequest, array $attributes): WithdrawRequest
    {
        $withdrawRequest->update($attributes);

        return $withdrawRequest;
    }

    public function delete(WithdrawRequest $withdrawRequest): void
    {
        $withdrawRequest->delete();
    }

    public function paginateForOwner(Model $owner, Request $request): LengthAwarePaginator
    {
        $search = WalletSearch::normalize($request->input('search'));

        return $owner->withdrawRequests()
            ->with('payoutRequest')
            ->when($search, function (Builder $query) use ($search): void {
                $query->where(function (Builder $inner) use ($search): void {
                    $inner->where('id', 'like', "%{$search}%")
                        ->orWhere('transaction_id', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate($request->integer('perPage', 16));
    }

    public function paginateAll(Request $request): LengthAwarePaginator
    {
        return WithdrawRequest::query()
            ->with(['user', 'payoutRequest'])
            ->when($request->input('search'), function (Builder $query, mixed $search) {
                $search = (string) $search;

                return $query->whereHasMorph(
                    'user',
                    [User::class, Provider::class],
                    function (Builder $q, string $type) use ($search): void {
                        if ($type === User::class) {
                            $q->where(function (Builder $inner) use ($search): void {
                                $inner->where('f_name', 'like', "%{$search}%")
                                    ->orWhere('l_name', 'like', "%{$search}%")
                                    ->orWhere('phone', 'like', "%{$search}%");
                            });

                            return;
                        }

                        $q->where(function (Builder $inner) use ($search): void {
                            $inner->where('name', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%");
                        });
                    },
                );
            })
            ->orderByRaw('status = ? DESC', [OperationStatusEnum::Pending->value])
            ->latest()
            ->paginate($request->integer('perPage', 16));
    }
}
