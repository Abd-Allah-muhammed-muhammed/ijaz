<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\Provider;
use Modules\Cms\Http\Resources\Dashboard\BannerResource;
use Modules\Cms\Services\BannerService;
use Modules\Orders\Enums\OrderStatusEnum;
use Modules\Orders\Http\Resources\Dashboard\OrderResource;
use Modules\Orders\Services\OrderService;
use Modules\Payout\Services\PayoutService;
use Modules\Wallet\Http\Resources\Dashboard\WalletResource;
use Modules\Wallet\Http\Resources\Dashboard\WalletTransactionResource;
use Modules\Wallet\Services\WalletService;

class HomeController extends Controller
{
    public function __construct(
        private readonly BannerService $bannerService,
        private readonly OrderService $orderService,
        private readonly PayoutService $payoutService,
        private readonly WalletService $walletService,
    ) {}

    public function __invoke()
    {
        /** @var Provider $auth */
        $auth = auth('provider')->user();
        $auth->load('wallet');
        $auth->wallet->setAttribute(
            'amount_in_transfer',
            $this->payoutService->sumInProgressAmountForRecipient($auth),
        );

        $stats = $this->orderService->providerHomeStats($auth);
        $auth->loadMissing('categories');
        $recommendOrders = $this->orderService->listRecommendedForProviderHome(
            $auth,
            categoryIds: $auth->categories->pluck('id')->all(),
        );
        $orders = $this->orderService->listWindowedForProviderHome($auth);
        $banners = $this->bannerService->all();

        return inertia('Provider/Home', [
            'totalOrders' => $stats['totalOrders'],
            'totalFinishedOrders' => $stats['totalFinishedOrders'],
            'wallet' => WalletResource::make($auth->wallet),
            'recentTransactions' => WalletTransactionResource::collection(
                $this->walletService->listRecentForWallet($auth->wallet, 5),
            ),
            'recommendOrders' => OrderResource::collection($recommendOrders),
            'banners' => BannerResource::collection($banners),
            'pendingOrders' => OrderResource::collection($orders->get(OrderStatusEnum::New->value, fn () => collect())?->take(3)),
            'approvedOrders' => OrderResource::collection($orders->get(OrderStatusEnum::OfferProvided->value, fn () => collect())?->take(3)),
            'inProgressOrders' => OrderResource::collection($orders->get(OrderStatusEnum::InProgress->value, fn () => collect())?->take(3)),
            'endedByProviderOrders' => OrderResource::collection($orders->get(OrderStatusEnum::EndedByProvider->value, fn () => collect())?->take(3)),
        ]);
    }
}
