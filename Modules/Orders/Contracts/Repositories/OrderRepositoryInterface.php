<?php

namespace Modules\Orders\Contracts\Repositories;

use App\Models\Provider;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Modules\Orders\Models\Order;

interface OrderRepositoryInterface
{
    public function paginateConversationMessages(Order $order, int $perPage = 15): ?LengthAwarePaginator;

    public function paginateForUser(User $user, int $perPage): LengthAwarePaginator;

    /**
     * @param  array{status?: mixed, date_from?: mixed, date_to?: mixed, search?: mixed}  $filters
     */
    public function paginateForProvider(Provider $provider, array $filters, int $perPage): LengthAwarePaginator;

    public function paginateRecommendedForProvider(Provider $provider, int $perPage): LengthAwarePaginator;

    /**
     * @return EloquentCollection<int, Order>
     */
    public function listRecommendedForProviderHome(Provider $provider, int $limit = 10): EloquentCollection;

    /**
     * @return Collection<string, Collection<int, Order>>
     */
    public function listWindowedForProviderHome(Provider $provider): Collection;

    /**
     * Dashboard home windowed latest orders (unscoped).
     * Same ROW_NUMBER + ORDER BY + LIMIT quirk as listWindowedForProviderHome.
     *
     * @return Collection<string, Collection<int, Order>>
     */
    public function listWindowedForDashboardHome(): Collection;

    public function countAll(): int;

    /**
     * @return array<string, int>
     */
    public function statusDistribution(): array;

    /**
     * @return array{totalOrders: int, totalFinishedOrders: int}
     */
    public function providerHomeStats(Provider $provider): array;

    /**
     * @param  array{status?: mixed, date_from?: mixed, date_to?: mixed, search?: mixed}  $filters
     */
    public function paginateForDashboard(array $filters, int $perPage): LengthAwarePaginator;

    /**
     * @return array<string, int>
     */
    public function dashboardStats(): array;

    /**
     * @param  array<string, mixed>  $data
     */
    public function createForUser(User $user, array $data): Order;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Order $order, array $data): Order;

    public function delete(Order $order): void;

    public function loadForUserShow(Order $order): Order;

    public function loadForProviderShow(Order $order, Provider $provider): Order;

    public function loadForDashboardShow(Order $order): Order;
}
