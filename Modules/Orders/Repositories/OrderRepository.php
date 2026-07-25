<?php

namespace Modules\Orders\Repositories;

use App\Models\Provider;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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
     * @return EloquentCollection<int, Order>
     */
    public function listRecommendedForProviderHome(Provider $provider, int $limit = 10): EloquentCollection
    {
        $categories = $provider->providerCategories()->pluck('category_id')->toArray();

        return Order::query()
            ->where('status', OrderStatusEnum::New)
            ->whereIn('category_id', $categories)
            ->whereNull('provider_id')
            ->whereNull('accepted_offer_id')
            ->withCount(['offers', 'media'])
            ->with(['category.translation', 'user'])
            ->latest()
            ->take($limit)
            ->get();
    }

    /**
     * @return Collection<string, Collection<int, Order>>
     */
    public function listWindowedForProviderHome(Provider $provider): Collection
    {
        $orderStatuses = [
            OrderStatusEnum::New,
            OrderStatusEnum::OfferProvided,
            OrderStatusEnum::EndedByProvider,
            OrderStatusEnum::InProgress,
        ];

        return Order::query()
            ->orderByRaw('ROW_NUMBER() OVER (PARTITION BY status ORDER BY created_at DESC)')
            ->whereIn('status', $orderStatuses)
            ->where(function ($query) use ($provider) {
                return $query
                    ->where(function ($query) use ($provider) {
                        return $query->whereHas('offers', fn ($q) => $q->where('provider_id', $provider->id)->where('status', OfferStatusEnum::Pending))
                            ->where('status', OrderStatusEnum::New);
                    })
                    ->orWhere('provider_id', $provider->id);
            })
            ->limit(count($orderStatuses) * 3)
            ->get()
            ->groupBy(fn ($i) => $i->status->value);
    }

    /**
     * @return Collection<string, Collection<int, Order>>
     */
    public function listWindowedForDashboardHome(): Collection
    {
        $orderStatuses = [
            OrderStatusEnum::New,
            OrderStatusEnum::OfferProvided,
            OrderStatusEnum::EndedByProvider,
            OrderStatusEnum::InProgress,
        ];

        return Order::query()
            ->with(['user', 'provider'])
            ->orderByRaw('ROW_NUMBER() OVER (PARTITION BY status ORDER BY created_at DESC)')
            ->limit(count($orderStatuses) * 3)
            ->whereIn('status', $orderStatuses)
            ->get()
            ->groupBy(fn ($i) => $i->status->value);
    }

    public function countAll(): int
    {
        return Order::query()->count('id');
    }

    /**
     * @return array<string, int>
     */
    public function statusDistribution(): array
    {
        return Order::query()
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->status->value => $item->count];
            })
            ->all();
    }

    /**
     * @return array{totalOrders: int, totalFinishedOrders: int}
     */
    public function providerHomeStats(Provider $provider): array
    {
        return [
            'totalOrders' => $provider->orders()->count(),
            'totalFinishedOrders' => $provider->orders()
                ->where('status', OrderStatusEnum::EndedByClient)
                ->count(),
        ];
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
