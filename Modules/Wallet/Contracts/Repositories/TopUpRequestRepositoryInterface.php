<?php

namespace Modules\Wallet\Contracts\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Wallet\Models\TopUpRequest;

interface TopUpRequestRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function createForOwner(Model $owner, array $attributes): TopUpRequest;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(TopUpRequest $topUpRequest, array $attributes): TopUpRequest;

    public function delete(TopUpRequest $topUpRequest): void;

    public function paginateForOwner(Model $owner, int $perPage): LengthAwarePaginator;

    public function paginateAll(Request $request): LengthAwarePaginator;
}
