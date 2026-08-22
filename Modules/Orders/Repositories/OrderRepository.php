<?php

namespace Modules\Orders\Repositories;

use App\Models\Provider;
use App\Models\User;
use App\Support\LookupCache;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;
use Modules\Chat\Models\Conversation;
use Modules\Orders\Contracts\Repositories\OrderRepositoryInterface;
use Modules\Orders\Enums\OfferStatusEnum;
use Modules\Orders\Enums\OrderStatusEnum;
use Modules\Orders\Models\Order;

class OrderRepository implements OrderRepositoryInterface
{
    public function findConversation(Order $order): ?Conversation
    {
        $conversation = $order->conversation;

        return $conversation instanceof Conversation ? $conversation : null;
    }

    public function paginateConversationMessages(Order $order, int $perPage = 15, ?string $search = null): ?LengthAwarePaginator
    {
        $chat = $this->findConversation($order);

        if (! $chat) {
            return null;
        }

        $query = $chat->messages()
            ->latest()
            ->with(['sender', 'media']);

        if ($search !== null && $search !== '') {
            $escaped = addcslashes($search, '%_\\');
            $query->where('content', 'like', '%'.$escaped.'%');
        }

        return $query
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

    /**
     * @param  array{status?: mixed, date_from?: mixed, date_to?: mixed, search?: mixed}  $filters
     */
    public function paginateForProvider(Provider $provider, array $filters, int $perPage): LengthAwarePaginator
    {
        return $provider->orders()
            ->with(['user'])
            ->withCount(['offers', 'media'])
            ->when(isset($filters['status']), fn ($q) => $q->where('status', $filters['status']))
            ->when(isset($filters['date_from']), fn ($q) => $q->whereDate('created_at', '>=', $filters['date_from']))
            ->when(isset($filters['date_to']), fn ($q) => $q->whereDate('created_at', '<=', $filters['date_to']))
            ->when(isset($filters['search']), function ($q) use ($filters) {
                $search = (string) $filters['search'];

                return $q->where('title', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate($perPage);
    }

    /**
     * @param  array{date_from?: mixed, search?: mixed}  $filters
     */
    public function paginateRecommendedForProvider(Provider $provider, array $filters, int $perPage): LengthAwarePaginator
    {
        return Order::whereIntegerInRaw('category_id', $provider->providerCategories()->pluck('category_id'))
            ->where('status', OrderStatusEnum::New)
            ->with(['user'])
            ->withCount(['offers', 'media'])
            ->whereNull('accepted_offer_id')
            ->when(isset($filters['date_from']), fn ($q) => $q->whereDate('created_at', '>=', $filters['date_from']))
            ->when(isset($filters['search']), function ($q) use ($filters) {
                $search = (string) $filters['search'];

                return $q->where('title', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate($perPage);
    }

    /**
     * @return EloquentCollection<int, Order>
     */
    public function listRecommendedForProviderHome(Provider $provider, int $limit = 10, ?array $categoryIds = null): EloquentCollection
    {
        $categories = $categoryIds ?? $provider->providerCategories()->pluck('category_id')->toArray();

        if ($categories === []) {
            return new EloquentCollection;
        }

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
        $stats = $provider->orders()
            ->selectRaw(
                'COUNT(*) as total_orders, SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as total_finished_orders',
                [OrderStatusEnum::EndedByClient->value],
            )
            ->first();

        return [
            'totalOrders' => (int) ($stats->total_orders ?? 0),
            'totalFinishedOrders' => (int) ($stats->total_finished_orders ?? 0),
        ];
    }

    /**
     * @param  array{status?: mixed, date_from?: mixed, date_to?: mixed, search?: mixed}  $filters
     */
    public function paginateForDashboard(array $filters, int $perPage): LengthAwarePaginator
    {
        return Order::query()
            ->with(['user', 'provider', 'city.translation', 'region.translation', 'category.translation'])
            ->withCount(['offers', 'media'])
            ->when(isset($filters['status']), fn ($q) => $q->where('status', $filters['status']))
            ->when(isset($filters['date_from']), fn ($q) => $q->whereDate('created_at', '>=', $filters['date_from']))
            ->when(isset($filters['date_to']), fn ($q) => $q->whereDate('created_at', '<=', $filters['date_to']))
            ->when(isset($filters['search']), function ($q) use ($filters) {
                $search = (string) $filters['search'];

                return $q->where('title', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate($perPage);
    }

    /**
     * @return array<string, int>
     */
    public function dashboardStats(): array
    {
        /** @var array<string, int> */
        return LookupCache::rememberFor('stats:orders:dashboard', 30, fn (): array => [
            'total' => Order::count(),
            'active' => Order::whereIn('status', [OrderStatusEnum::PaymentCompleted, OrderStatusEnum::InProgress])->count(),
            'pending' => Order::whereIn('status', [OrderStatusEnum::New, OrderStatusEnum::Hold, OrderStatusEnum::OfferProvided])->count(),
            'completed' => Order::whereIn('status', [OrderStatusEnum::EndedByProvider, OrderStatusEnum::EndedByClient])->count(),
            'cancelled' => Order::whereIn('status', [OrderStatusEnum::CancelledByProvider, OrderStatusEnum::CancelledByClient, OrderStatusEnum::Refunded])->count(),
        ]);
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

    public function lockForUpdate(Order $order): Order
    {
        /** @var Order $locked */
        $locked = Order::query()
            ->whereKey($order->getKey())
            ->lockForUpdate()
            ->firstOrFail();

        return $locked;
    }

    /**
     * @return LazyCollection<int, Order>
     */
    public function listDueForWalletSettlement(CarbonInterface $endedBefore): LazyCollection
    {
        $endedStatuses = [
            OrderStatusEnum::EndedByProvider,
            OrderStatusEnum::EndedByClient,
        ];

        return Order::query()
            ->whereIn('status', $endedStatuses)
            ->whereNull('wallet_settled_at')
            ->whereHas('histories', function ($query) use ($endedBefore, $endedStatuses): void {
                $query->whereIn('status', $endedStatuses)
                    ->where('created_at', '<=', $endedBefore);
            })
            ->with(['user', 'provider', 'acceptedOffer'])
            ->lazyById();
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
            'conversation.user1',
            'conversation.user2',
        ]);
        $order->loadCount([
            'offers',
            'media',
        ]);

        return $order;
    }
}
