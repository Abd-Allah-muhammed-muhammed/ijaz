<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Resources\Dashboard\ProviderResource;
use App\Http\Resources\Dashboard\UserResource;
use App\Services\Dashboard\DashboardHomeService;
use Modules\Orders\Enums\OrderStatusEnum;
use Modules\Orders\Http\Resources\Dashboard\OrderResource;

class HomeController extends Controller
{
    public function __construct(
        private readonly DashboardHomeService $dashboardHomeService,
    ) {}

    public function __invoke()
    {
        $home = $this->dashboardHomeService->forHome();
        $orders = $home->windowedOrders;

        return inertia('Dashboard/Home', [
            'stats' => $home->stats,
            'chartData' => $home->chartData,
            'orderStatusDistribution' => $home->orderStatusDistribution,
            'latestUsers' => UserResource::collection($home->latestUsers),
            'latestProviders' => ProviderResource::collection($home->latestProviders),
            'pendingOrders' => OrderResource::collection(collect($orders->get(OrderStatusEnum::New->value, collect()))->take(3)),
            'approvedOrders' => OrderResource::collection(collect($orders->get(OrderStatusEnum::OfferProvided->value, collect()))->take(3)),
            'inProgressOrders' => OrderResource::collection(collect($orders->get(OrderStatusEnum::InProgress->value, collect()))->take(3)),
            'endedByProviderOrders' => OrderResource::collection(collect($orders->get(OrderStatusEnum::EndedByProvider->value, collect()))->take(3)),
        ]);
    }
}
