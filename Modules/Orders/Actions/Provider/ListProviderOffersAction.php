<?php

namespace Modules\Orders\Actions\Provider;

use App\Models\Provider;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Orders\Contracts\Repositories\OrderOfferRepositoryInterface;

class ListProviderOffersAction
{
    public function __construct(
        private readonly OrderOfferRepositoryInterface $offers,
    ) {}

    /**
     * @param  array{status?: mixed, search?: mixed}  $filters
     */
    public function handle(Provider $provider, array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->offers->paginateForProvider($provider, $filters, $perPage);
    }
}
