<?php

namespace Modules\Payout\Contracts\Repositories;

use Illuminate\Database\Eloquent\Model;
use Modules\Payout\Models\PayoutRequest;

interface PayoutRequestRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): PayoutRequest;

    public function existsForOperation(Model $operation): bool;
}
