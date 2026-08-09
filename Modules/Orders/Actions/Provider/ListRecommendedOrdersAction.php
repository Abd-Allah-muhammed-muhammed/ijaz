<?php

namespace Modules\Orders\Actions\Provider;

use App\Models\Provider;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Orders\Contracts\Repositories\OrderRepositoryInterface;

class ListRecommendedOrdersAction
{
    /**
     * Period dropdown values (days) supported by the New Orders filter.
     *
     * @var array<string, int>
     */
    private const PERIOD_DAYS = [
        '30' => 30,
        '90' => 90,
        '180' => 180,
        '365' => 365,
    ];

    public function __construct(
        private readonly OrderRepositoryInterface $orders,
    ) {}

    /**
     * @param  array{period?: mixed, date_from?: mixed, search?: mixed}  $filters
     */
    public function handle(Provider $provider, array $filters, int $perPage): LengthAwarePaginator
    {
        if (isset($filters['period'])) {
            $period = (string) $filters['period'];

            if (isset(self::PERIOD_DAYS[$period])) {
                $filters['date_from'] = now()->subDays(self::PERIOD_DAYS[$period])->toDateString();
            }

            unset($filters['period']);
        }

        return $this->orders->paginateRecommendedForProvider($provider, $filters, $perPage);
    }
}
