<?php

namespace App\DTOs\Dashboard;

use App\Models\Provider;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Modules\Orders\Models\Order;

final readonly class DashboardHomeData
{
    /**
     * @param  array{totalUsers: int, totalProviders: int, totalOrders: int, totalRevenue: float|int|string}  $stats
     * @param  array{dates: list<string>, userRegistrations: list<int>, providerRegistrations: list<int>, revenue: list<float>}  $chartData
     * @param  array<string, int>  $orderStatusDistribution
     * @param  EloquentCollection<int, User>  $latestUsers
     * @param  EloquentCollection<int, Provider>  $latestProviders
     * @param  Collection<string, Collection<int, Order>>  $windowedOrders
     */
    public function __construct(
        public array $stats,
        public array $chartData,
        public array $orderStatusDistribution,
        public EloquentCollection $latestUsers,
        public EloquentCollection $latestProviders,
        public Collection $windowedOrders,
    ) {}
}
