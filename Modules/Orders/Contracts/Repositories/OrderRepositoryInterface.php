<?php

namespace Modules\Orders\Contracts\Repositories;

use App\Models\Provider;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Orders\Models\Order;

interface OrderRepositoryInterface
{
    public function paginateConversationMessages(Order $order, int $perPage = 15): ?LengthAwarePaginator;

    public function paginateForUser(User $user, int $perPage): LengthAwarePaginator;

    public function paginateForProvider(Provider $provider, int $perPage): LengthAwarePaginator;

    public function paginateRecommendedForProvider(Provider $provider, int $perPage): LengthAwarePaginator;

    /**
     * @param  array{status?: mixed, date_from?: mixed, date_to?: mixed}  $filters
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
