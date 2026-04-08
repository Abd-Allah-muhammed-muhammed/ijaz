<?php

namespace App\Http\Controllers\Provider;

use App\Enums\Order\OfferStatusEnum;
use App\Enums\Order\OrderStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\Dashboard\BannerResource;
use App\Http\Resources\Dashboard\OrderResource;
use App\Models\Banner;
use App\Models\Order;

class HomeController extends Controller
{
    public function __invoke()
    {
        $auth = auth('provider')->user();
        $categories = $auth->providerCategories()->pluck('category_id')->toArray();
        $orderQ = auth('provider')->user()->orders();
        $totalOrders = $orderQ->count();
        $totalFinishedOrders = $orderQ->where('status', OrderStatusEnum::EndedByClient)->count();
        $recommendOrders = Order::query()
            ->where('status', OrderStatusEnum::New)
            ->whereIn('category_id', $categories)
            ->whereNull('provider_id')
            ->whereNull('accepted_offer_id')
            ->withCount(['offers', 'media'])
            ->with(['category.translation', 'user'])
            ->latest()
            ->take(10)
            ->get();

        $orderStatuses = [
            OrderStatusEnum::New,
            OrderStatusEnum::OfferProvided,
            OrderStatusEnum::EndedByProvider,
            OrderStatusEnum::InProgress,
        ];

        $orders = Order::query()
            ->orderByRaw('ROW_NUMBER() OVER (PARTITION BY status ORDER BY created_at DESC)')
            ->whereIn('status', $orderStatuses)
            ->where(function ($query) use ($auth) {
                return $query
                    ->where(function ($query) use ($auth) {
                        return $query->whereHas('offers', fn ($q) => $q->where('provider_id', $auth->id)->where('status', OfferStatusEnum::Pending))
                            ->where('status', OrderStatusEnum::New);
                    })
                    ->orWhere('provider_id', $auth->id);
            })
            ->limit(count($orderStatuses) * 3)
            ->get()
            ->groupBy(fn ($i) => $i->status->value);

        $banners = Banner::all();

        return inertia('Provider/Home', [
            'totalOrders' => $totalOrders,
            'totalFinishedOrders' => $totalFinishedOrders,
            'recommendOrders' => OrderResource::collection($recommendOrders),
            'banners' => BannerResource::collection($banners),
            'pendingOrders' => OrderResource::collection($orders->get(OrderStatusEnum::New->value, fn () => collect())?->take(3)),
            'approvedOrders' => OrderResource::collection($orders->get(OrderStatusEnum::OfferProvided->value, fn () => collect())?->take(3)),
            'inProgressOrders' => OrderResource::collection($orders->get(OrderStatusEnum::InProgress->value, fn () => collect())?->take(3)),
            'endedByProviderOrders' => OrderResource::collection($orders->get(OrderStatusEnum::EndedByProvider->value, fn () => collect())?->take(3)),
        ]);
    }
}
