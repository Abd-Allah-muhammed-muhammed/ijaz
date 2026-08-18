<?php

namespace Modules\Payout\Repositories;

use Illuminate\Database\Eloquent\Model;
use Modules\Payout\Contracts\Repositories\PayoutRequestRepositoryInterface;
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
}
