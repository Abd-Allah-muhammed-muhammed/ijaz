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

    public function lockForUpdate(WithdrawRequest $withdrawRequest): WithdrawRequest;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(WithdrawRequest $withdrawRequest, array $attributes): WithdrawRequest;

    public function delete(WithdrawRequest $withdrawRequest): void;

    public function paginateForOwner(Model $owner, Request $request): LengthAwarePaginator;

    public function paginateAll(Request $request): LengthAwarePaginator;
}
