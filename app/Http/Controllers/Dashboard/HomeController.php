<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\Order\OrderStatusEnum;
use App\Enums\Payment\PaymentStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\Dashboard\OrderResource;
use App\Http\Resources\Dashboard\ProviderResource;
use App\Http\Resources\Dashboard\UserResource;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Provider;
use App\Models\User;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function __invoke()
    {
        $orderStatuses = [
            OrderStatusEnum::New,
            OrderStatusEnum::OfferProvided,
            OrderStatusEnum::EndedByProvider,
            OrderStatusEnum::InProgress,
        ];

        // Get latest orders for each status using subquery approach
        $orders = Order::query()
            ->with(['user', 'provider'])
            ->orderByRaw('ROW_NUMBER() OVER (PARTITION BY status ORDER BY created_at DESC)')
            ->limit(count($orderStatuses) * 3)
            ->whereIn('status', $orderStatuses)
            ->get()
            ->groupBy(fn ($i) => $i->status->value);

        // KPI Stats
        $stats = [
            'totalUsers' => User::count('id'),
            'totalProviders' => Provider::count('id'),
            'totalOrders' => Order::count('id'),
            'totalRevenue' => Payment::query()->where('status', '=', PaymentStatusEnum::Accepted)->sum('amount'),
        ];

        // Registration Stats (Last 30 days)
        $last30Days = now()->subDays(30);
        $userRegistrations = User::query()->where('created_at', '>=', $last30Days)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('count', 'date');

        $providerRegistrations = Provider::query()->where('created_at', '>=', $last30Days)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('count', 'date');

        $revenueDaily = Payment::query()->where('status', '=', PaymentStatusEnum::Accepted)
            ->where('created_at', '>=', $last30Days)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('sum(amount) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('total', 'date');

        // Order Status Distribution
        $orderStatusDistribution = Order::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->status->value => $item->count];
            });

        // Prepare dates for charts
        $period = CarbonPeriod::create($last30Days, now());
        $chartData = [
            'dates' => [],
            'userRegistrations' => [],
            'providerRegistrations' => [],
            'revenue' => [],
        ];

        foreach ($period as $date) {
            $formattedDate = $date->format('Y-m-d');
            $chartData['dates'][] = $formattedDate;
            $chartData['userRegistrations'][] = $userRegistrations->get($formattedDate, 0);
            $chartData['providerRegistrations'][] = $providerRegistrations->get($formattedDate, 0);
            $chartData['revenue'][] = (float) $revenueDaily->get($formattedDate, 0);
        }

        return inertia('Dashboard/Home', [
            'stats' => $stats,
            'chartData' => $chartData,
            'orderStatusDistribution' => $orderStatusDistribution,
            'latestUsers' => UserResource::collection(User::query()->withCount(['orders'])->limit(4)->orderByDesc('created_at')->get()),
            'latestProviders' => ProviderResource::collection(Provider::query()->withCount(['orders', 'reviews'])->withAvg('reviews', 'rating')->limit(4)->orderByDesc('created_at')->get()),
            'pendingOrders' => OrderResource::collection(collect($orders->get(OrderStatusEnum::New->value, collect()))->take(3)),
            'approvedOrders' => OrderResource::collection(collect($orders->get(OrderStatusEnum::OfferProvided->value, collect()))->take(3)),
            'inProgressOrders' => OrderResource::collection(collect($orders->get(OrderStatusEnum::InProgress->value, collect()))->take(3)),
            'endedByProviderOrders' => OrderResource::collection(collect($orders->get(OrderStatusEnum::EndedByProvider->value, collect()))->take(3)),
        ]);
    }

    public function test()
    {
        return inertia('RTLDemo');
    }
}
