<?php

namespace Modules\Wallet\Repositories;

use App\Enums\OperationStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Wallet\Contracts\Repositories\WithdrawRequestRepositoryInterface;
use Modules\Wallet\Models\WithdrawRequest;

class WithdrawRequestRepository implements WithdrawRequestRepositoryInterface
{
    public function createForOwner(Model $owner, array $attributes): WithdrawRequest
    {
        /** @var WithdrawRequest $withdrawRequest */
        $withdrawRequest = $owner->withdrawRequests()->create($attributes);

        return $withdrawRequest;
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

    public function paginateForOwner(Model $owner, int $perPage): LengthAwarePaginator
    {
        return $owner->withdrawRequests()
            ->latest()
            ->paginate($perPage);
    }

    public function paginateAll(int $perPage): LengthAwarePaginator
    {
        return WithdrawRequest::query()
            ->with('user')
            ->orderByRaw('status = ? DESC', [OperationStatusEnum::Pending->value])
            ->latest()
            ->paginate($perPage);
    }
}
