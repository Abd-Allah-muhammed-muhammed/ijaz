<?php

namespace Modules\Wallet\Contracts\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Wallet\Models\WithdrawRequest;

interface WithdrawRequestRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function createForOwner(Model $owner, array $attributes): WithdrawRequest;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(WithdrawRequest $withdrawRequest, array $attributes): WithdrawRequest;

    public function delete(WithdrawRequest $withdrawRequest): void;

    public function paginateForOwner(Model $owner, int $perPage): LengthAwarePaginator;

    public function paginateAll(Request $request): LengthAwarePaginator;
}
