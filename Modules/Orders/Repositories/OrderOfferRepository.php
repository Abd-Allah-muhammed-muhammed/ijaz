<?php

namespace Modules\Orders\Repositories;

use App\Models\Provider;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Modules\Orders\Contracts\Repositories\OrderOfferRepositoryInterface;
use Modules\Orders\Enums\OfferStatusEnum;
use Modules\Orders\Models\Order;
use Modules\Orders\Models\OrderOffer;

class OrderOfferRepository implements OrderOfferRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Order $order, array $data): OrderOffer
    {
        return $order->offers()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(OrderOffer $offer, array $data): OrderOffer
    {
        $offer->update($data);

        return $offer;
    }

    public function delete(OrderOffer $offer): void
    {
        $offer->delete();
    }

    public function providerHasActiveOffer(Order $order, Provider $provider): bool
    {
        return $order->offers()
            ->where('provider_id', $provider->id)
            ->whereIn('status', [OfferStatusEnum::Pending, OfferStatusEnum::Accepted])
            ->exists();
    }

    public function getPendingCreatedBefore(\DateTimeInterface $createdBefore): EloquentCollection
    {
        return OrderOffer::query()
            ->where('status', OfferStatusEnum::Pending)
            ->where('created_at', '<=', $createdBefore)
            ->with('provider')
            ->get();
    }

    /**
     * @param  array{status?: mixed, search?: mixed}  $filters
     */
    public function paginateForProvider(Provider $provider, array $filters, int $perPage): LengthAwarePaginator
    {
        return $provider->orderOffers()
            ->with(['order.user'])
            ->when(isset($filters['status']), fn ($q) => $q->where('status', $filters['status']))
            ->when(isset($filters['search']), function ($q) use ($filters) {
                $search = (string) $filters['search'];

                return $q->whereHas('order', fn ($orderQuery) => $orderQuery->where('title', 'like', "%{$search}%"));
            })
            ->latest()
            ->paginate($perPage);
    }

    public function rejectPendingSiblings(Order $order, OrderOffer $except): EloquentCollection
    {
        $siblings = $order->offers()
            ->where('status', OfferStatusEnum::Pending)
            ->whereKeyNot($except->getKey())
            ->with('provider')
            ->get();

        foreach ($siblings as $sibling) {
            $sibling->update(['status' => OfferStatusEnum::Rejected]);
        }

        return $siblings;
    }
}
