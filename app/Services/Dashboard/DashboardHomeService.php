<?php

namespace App\Services\Dashboard;

use App\Actions\Dashboard\AssembleDashboardChartDataAction;
use App\DTOs\Dashboard\DashboardHomeData;
use App\Services\Provider\ProviderManagementService;
use App\Services\User\UserManagementService;
use Modules\Orders\Services\OrderService;
use Modules\Payment\Services\PaymentService;

class DashboardHomeService
{
    public function __construct(
        private readonly OrderService $orderService,
        private readonly UserManagementService $userService,
        private readonly ProviderManagementService $providerService,
        private readonly PaymentService $paymentService,
        private readonly AssembleDashboardChartDataAction $assembleChartData,
    ) {}

    public function forHome(): DashboardHomeData
    {
        $last30Days = now()->subDays(30);

        return new DashboardHomeData(
            stats: [
                'totalUsers' => $this->userService->countAll(),
                'totalProviders' => $this->providerService->countAll(),
                'totalOrders' => $this->orderService->countAll(),
                'totalRevenue' => $this->paymentService->sumAcceptedAmount(),
            ],
            chartData: $this->assembleChartData->handle(
                $last30Days,
                now(),
                $this->userService->registrationCountsSince($last30Days),
                $this->providerService->registrationCountsSince($last30Days),
                $this->paymentService->acceptedDailyTotalsSince($last30Days),
            ),
            orderStatusDistribution: $this->orderService->statusDistribution(),
            latestUsers: $this->userService->latestForDashboard(),
            latestProviders: $this->providerService->latestForDashboard(),
            windowedOrders: $this->orderService->listWindowedForDashboardHome(),
        );
    }
}
