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

    /**
     * @param  array{status?: mixed, date_from?: mixed, date_to?: mixed, search?: mixed}  $filters
     */
    public function handle(Provider $provider, array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->orders->paginateForProvider($provider, $filters, $perPage);
    }
}
