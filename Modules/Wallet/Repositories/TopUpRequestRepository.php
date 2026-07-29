<?php

namespace Modules\Wallet\Repositories;

use App\Enums\OperationStatusEnum;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
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

    public function paginateAll(Request $request): LengthAwarePaginator
    {
        return TopUpRequest::query()
            ->with('user')
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
