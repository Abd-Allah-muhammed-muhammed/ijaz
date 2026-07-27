<?php

namespace Modules\Wallet\Repositories;

use App\Enums\OperationStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Wallet\Contracts\Repositories\TopUpRequestRepositoryInterface;
use Modules\Wallet\Models\TopUpRequest;

class TopUpRequestRepository implements TopUpRequestRepositoryInterface
{
    public function createForOwner(Model $owner, array $attributes): TopUpRequest
    {
        /** @var TopUpRequest $topUpRequest */
        $topUpRequest = $owner->topUpRequests()->create($attributes);

        return $topUpRequest;
    }

    public function update(TopUpRequest $topUpRequest, array $attributes): TopUpRequest
    {
        $topUpRequest->update($attributes);

        return $topUpRequest;
    }

    public function delete(TopUpRequest $topUpRequest): void
    {
        $topUpRequest->delete();
    }

    public function paginateForOwner(Model $owner, int $perPage): LengthAwarePaginator
    {
        return $owner->topUpRequests()
            ->latest()
            ->paginate($perPage);
    }

    public function paginateAll(int $perPage): LengthAwarePaginator
    {
        return TopUpRequest::query()
            ->with('user')
            ->orderByRaw('status = ? DESC', [OperationStatusEnum::Pending->value])
            ->latest()
            ->paginate($perPage);
    }
}
