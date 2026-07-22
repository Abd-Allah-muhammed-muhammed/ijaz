<?php

namespace Modules\Orders\Actions\User;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Orders\Contracts\Repositories\OrderRepositoryInterface;

class ListUserOrdersAction
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
    ) {}

    public function handle(User $user, int $perPage): LengthAwarePaginator
    {
        return $this->orders->paginateForUser($user, $perPage);
    }
}
