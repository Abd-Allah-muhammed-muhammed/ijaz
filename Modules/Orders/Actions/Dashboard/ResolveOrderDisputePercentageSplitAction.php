<?php

namespace Modules\Orders\Actions\Dashboard;

use App\Models\Admin;
use Illuminate\Support\Facades\DB;
use Modules\Orders\Actions\LogOrderStatusHistoryAction as LogOrderStatusHistory;
use Modules\Orders\Contracts\Repositories\OrderRepositoryInterface;
use Modules\Orders\DTOs\OrderHeldAmountData;
use Modules\Orders\Enums\OrderDisputeResolutionEnum;
use Modules\Orders\Enums\OrderStatusEnum;
use Modules\Orders\Exceptions\OrdersException;
use Modules\Orders\Models\Order;
use Modules\Orders\Notifications\OrderDisputeResolvedNotification;
use Modules\Wallet\Services\WalletService;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Percentage-split dispute resolution for orders.
 *
 * User share: proportional reversal of pending_debit (refund).
 * Provider share: proportional release of pending_credit to balance (net of provider fee).
 */
class ResolveOrderDisputePercentageSplitAction
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly LogOrderStatusHistory $logStatusHistory,
        private readonly WalletService $walletService,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(
        Order $order,
        Admin $admin,
        int $userPercentage,
        ?string $notes = null,
    ): Order {
        if ($userPercentage < 0 || $userPercentage > 100) {
            throw new OrdersException('order.invalid_user_percentage', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return DB::transaction(function () use ($order, $admin, $userPercentage, $notes) {
            $order = $this->orderRepository->lockForUpdate($order);

            if ($order->status->isNot(OrderStatusEnum::Disputed)) {
                throw new OrdersException('order.dispute_already_resolved', Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $fromStatus = $order->status->value;
            $providerPercentage = 100 - $userPercentage;
            $held = OrderHeldAmountData::fromOrder($order);

            $order->loadMissing(['user', 'provider', 'acceptedOffer']);

            if ($order->user !== null) {
                $order->user->wallet()->lockForUpdate()->firstOrCreate();
            }

            if ($order->provider !== null) {
                $order->provider->wallet()->lockForUpdate()->firstOrCreate();
            }

            $userGrossShare = round($held->gross * $userPercentage / 100, 2);
            $providerGrossShare = round($held->gross - $userGrossShare, 2);
            $userFeeShare = round((float) $order->user_fees * $userPercentage / 100, 2);
            $providerFeeShare = round((float) $order->provider_fees * $providerPercentage / 100, 2);
            $userRefundShare = round($userGrossShare + $userFeeShare, 2);
            $providerNetShare = round($providerGrossShare - $providerFeeShare, 2);

            if ($order->user !== null && $userRefundShare > 0) {
                $this->walletService->reversePendingDebit(
                    $order->user,
                    $userRefundShare,
                    $held->operation,
                    "Order#{$order->id} dispute split — user {$userPercentage}%",
                );
            }

            if ($order->provider !== null && $providerGrossShare > 0) {
                $this->walletService->releasePendingCreditToBalance(
                    $order->provider,
                    $providerGrossShare,
                    $providerNetShare,
                    $held->operation,
                    "Order#{$order->id} dispute split — provider {$providerPercentage}%",
                );
            }

            if ($order->user !== null) {
                $remainingUserDebit = round((float) $order->user->wallet->fresh()->pending_debit, 2);
                if ($remainingUserDebit > 0) {
                    $this->walletService->reversePendingDebit(
                        $order->user,
                        $remainingUserDebit,
                        $held->operation,
                        "Order#{$order->id} dispute split — user hold cleared",
                    );
                }
            }

            if ($order->provider !== null) {
                $remainingProviderCredit = round((float) $order->provider->wallet->fresh()->pending_credit, 2);
                if ($remainingProviderCredit > 0) {
                    $this->walletService->reversePendingCredit(
                        $order->provider,
                        $remainingProviderCredit,
                        $held->operation,
                        "Order#{$order->id} dispute split — voided provider remainder",
                    );
                }

                $feeHold = (float) $order->provider->wallet->fresh()->pending_debit;
                if ($feeHold < 0) {
                    $this->walletService->reversePendingDebit(
                        $order->provider,
                        $feeHold,
                        $held->operation,
                        "Order#{$order->id} dispute split — fee hold closed",
                    );
                }
            }

            $resolution = OrderDisputeResolutionEnum::PercentageSplit;
            $order = Order::withoutEvents(
                fn () => $this->orderRepository->update($order, [
                    'status' => OrderStatusEnum::Settled,
                    'dispute_user_percentage' => $userPercentage,
                    'dispute_user_amount' => $userRefundShare,
                    'dispute_provider_amount' => $providerNetShare,
                    'wallet_settled_at' => now(),
                ])
            );

            $historyReason = sprintf(
                '%s:%d/%d',
                $resolution->historyReason(),
                $userPercentage,
                $providerPercentage,
            );

            $this->logStatusHistory->handle(
                $order,
                $admin,
                $fromStatus,
                OrderStatusEnum::Settled->value,
                reason: $historyReason,
                notes: $notes,
            );

            $order->loadMissing(['user', 'provider']);
            $notification = new OrderDisputeResolvedNotification(
                $order,
                $resolution,
                userPercentage: $order->dispute_user_percentage,
                providerPercentage: 100 - (int) $order->dispute_user_percentage,
                userAmount: (float) $order->dispute_user_amount,
                providerAmount: (float) $order->dispute_provider_amount,
            );
            $order->user?->notify($notification);
            $order->provider?->notify($notification);

            return $order->fresh(['user', 'provider', 'acceptedOffer', 'histories']);
        });
    }
}
