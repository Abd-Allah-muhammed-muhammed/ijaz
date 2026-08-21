<?php

namespace Modules\Payout\Contracts\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Modules\Payout\Models\PayoutRequest;

interface PayoutRequestRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): PayoutRequest;

    public function existsForOperation(Model $operation): bool;

    public function findForOperation(Model $operation): ?PayoutRequest;

    public function lockForUpdate(PayoutRequest $payoutRequest): PayoutRequest;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(PayoutRequest $payoutRequest, array $attributes): PayoutRequest;

    public function paginateActionableForDashboard(Request $request): LengthAwarePaginator;
}
