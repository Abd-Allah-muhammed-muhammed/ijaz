<?php

namespace Modules\Orders\Actions\Provider;

use App\Models\Provider;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Orders\Contracts\Repositories\OrderRepositoryInterface;

class ListProviderOrdersAction
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
    ) {}

    public function handle(Provider $provider, int $perPage): LengthAwarePaginator
    {
        return $this->orders->paginateForProvider($provider, $perPage);
    }
}
