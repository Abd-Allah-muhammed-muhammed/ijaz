<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\Provider;
use Modules\Cms\Http\Resources\Dashboard\BannerResource;
use Modules\Cms\Services\BannerService;
use Modules\Orders\Enums\OrderStatusEnum;
use Modules\Orders\Http\Resources\Dashboard\OrderResource;
use Modules\Orders\Services\OrderService;

class HomeController extends Controller
{
    public function __construct(
        private readonly BannerService $bannerService,
        private readonly OrderService $orderService,
    ) {}

    public function __invoke()
    {
        /** @var Provider $auth */
        $auth = auth('provider')->user();

        $stats = $this->orderService->providerHomeStats($auth);
        $recommendOrders = $this->orderService->listRecommendedForProviderHome($auth);
        $orders = $this->orderService->listWindowedForProviderHome($auth);
        $banners = $this->bannerService->all();

        return inertia('Provider/Home', [
            'totalOrders' => $stats['totalOrders'],
            'totalFinishedOrders' => $stats['totalFinishedOrders'],
            'recommendOrders' => OrderResource::collection($recommendOrders),
            'banners' => BannerResource::collection($banners),
            'pendingOrders' => OrderResource::collection($orders->get(OrderStatusEnum::New->value, fn () => collect())?->take(3)),
            'approvedOrders' => OrderResource::collection($orders->get(OrderStatusEnum::OfferProvided->value, fn () => collect())?->take(3)),
            'inProgressOrders' => OrderResource::collection($orders->get(OrderStatusEnum::InProgress->value, fn () => collect())?->take(3)),
            'endedByProviderOrders' => OrderResource::collection($orders->get(OrderStatusEnum::EndedByProvider->value, fn () => collect())?->take(3)),
        ]);
    }
}
