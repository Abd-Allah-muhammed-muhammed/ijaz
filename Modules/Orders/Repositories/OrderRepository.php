<?php

namespace Modules\Orders\Repositories;

use App\Models\Provider;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Orders\Contracts\Repositories\OrderRepositoryInterface;
use Modules\Orders\Enums\OfferStatusEnum;
use Modules\Orders\Enums\OrderStatusEnum;
use Modules\Orders\Models\Order;

class OrderRepository implements OrderRepositoryInterface
{
    public function paginateConversationMessages(Order $order, int $perPage = 15): ?LengthAwarePaginator
    {
        $chat = $order->conversation;

        if (! $chat) {
            return null;
        }

        return $chat->messages()
            ->latest()
            ->with(['sender', 'attachments'])
            ->paginate($perPage)
            ->withQueryString();
    }

    public function paginateForUser(User $user, int $perPage): LengthAwarePaginator
    {
        return $user->orders()
            ->withCount(['offers'])
            ->with(['category.translation'])
            ->latest()
            ->paginate($perPage);
    }

    public function paginateForProvider(Provider $provider, int $perPage): LengthAwarePaginator
    {
        return $provider->orders()
            ->with(['user'])
            ->withCount(['offers', 'media'])
            ->latest()
            ->paginate($perPage);
    }

    public function paginateRecommendedForProvider(Provider $provider, int $perPage): LengthAwarePaginator
    {
        return Order::whereIntegerInRaw('category_id', $provider->providerCategories()->pluck('category_id'))
            ->where('status', OrderStatusEnum::New)
            ->with(['user'])
            ->latest()
            ->withCount(['offers', 'media'])
            ->whereNull('accepted_offer_id')
            ->paginate($perPage);
    }

    /**
     * @param  array{status?: mixed, date_from?: mixed, date_to?: mixed}  $filters
     */
    public function paginateForDashboard(array $filters, int $perPage): LengthAwarePaginator
    {
        return Order::query()
            ->with(['user', 'provider', 'city.translation', 'region.translation', 'category.translation'])
            ->withCount(['offers', 'media'])
            ->when(isset($filters['status']), fn ($q) => $q->where('status', $filters['status']))
            ->when(isset($filters['date_from']), fn ($q) => $q->whereDate('created_at', '>=', $filters['date_from']))
            ->when(isset($filters['date_to']), fn ($q) => $q->whereDate('created_at', '<=', $filters['date_to']))
            ->latest()
            ->paginate($perPage);
    }

    /**
     * @return array<string, int>
     */
    public function dashboardStats(): array
    {
        return [
            'total' => Order::count(),
            'active' => Order::whereIn('status', [OrderStatusEnum::PaymentCompleted, OrderStatusEnum::InProgress])->count(),
            'pending' => Order::whereIn('status', [OrderStatusEnum::New, OrderStatusEnum::Hold, OrderStatusEnum::OfferProvided])->count(),
            'completed' => Order::whereIn('status', [OrderStatusEnum::EndedByProvider, OrderStatusEnum::EndedByClient])->count(),
            'cancelled' => Order::whereIn('status', [OrderStatusEnum::CancelledByProvider, OrderStatusEnum::CancelledByClient, OrderStatusEnum::Refunded])->count(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createForUser(User $user, array $data): Order
    {
        return $user->orders()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Order $order, array $data): Order
    {
        $order->update($data);

        return $order;
    }

    public function delete(Order $order): void
    {
        $order->delete();
    }

    public function loadForUserShow(Order $order): Order
    {
        $order->load([
            'offers.provider',
            'category.translation',
            'provider',
            'media',
            'skills.translation',
            'city.translation',
            'region.translation',
        ]);

        return $order;
    }

    public function loadForProviderShow(Order $order, Provider $provider): Order
    {
        $order->load([
            'category',
            'provider',
            'media',
            'offers' => function ($query) use ($provider) {
                $query->where('provider_id', $provider->id);
            },
            'user',
            'skills.translation',
            'city.translation',
            'region.translation',
            'reviews',
        ]);
        $order->loadCount([
            'offers',
            'media',
        ]);

        return $order;
    }

    public function loadForDashboardShow(Order $order): Order
    {
        $order->load([
            'category.translation',
            'media',
            'offers' => fn ($q) => $q->with(['provider'])
                ->orderByRaw('CASE WHEN status = ? THEN 0 ELSE 1 END', [OfferStatusEnum::Accepted->value])
                ->orderByDesc('created_at'),
            'user',
            'provider' => function ($q) {
                $q->withAvg('reviews', 'rating');
            },
            'skills.translation',
            'city.translation',
            'region.translation',
            'reviews',
        ]);
        $order->loadCount([
            'offers',
            'media',
        ]);

        return $order;
    }
}
