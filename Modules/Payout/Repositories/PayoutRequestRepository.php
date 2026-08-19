<?php

namespace Modules\Payout\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Modules\Payout\Contracts\Repositories\PayoutRequestRepositoryInterface;
use Modules\Payout\Enums\PayoutStatusEnum;
use Modules\Payout\Models\PayoutRequest;

class PayoutRequestRepository implements PayoutRequestRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): PayoutRequest
    {
        return PayoutRequest::query()->create($attributes);
    }

    public function existsForOperation(Model $operation): bool
    {
        return PayoutRequest::query()
            ->where('operation_type', $operation::class)
            ->where('operation_id', $operation->getKey())
            ->exists();
    }

    public function lockForUpdate(PayoutRequest $payoutRequest): PayoutRequest
    {
        return PayoutRequest::query()
            ->whereKey($payoutRequest->getKey())
            ->lockForUpdate()
            ->firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(PayoutRequest $payoutRequest, array $attributes): PayoutRequest
    {
        $payoutRequest->update($attributes);

        return $payoutRequest->fresh();
    }

    public function paginateActionableForDashboard(Request $request): LengthAwarePaginator
    {
        $perPage = (int) $request->input('per_page', 10);

        return PayoutRequest::query()
            ->with(['recipient', 'makerAdmin', 'processedByAdmin'])
            ->whereIn('status', [
                PayoutStatusEnum::Pending->value,
                PayoutStatusEnum::Failed->value,
            ])
            ->latest()
            ->paginate($perPage > 0 ? $perPage : 10)
            ->withQueryString();
    }
}
