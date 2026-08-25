<?php

namespace Modules\Orders\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Orders\Contracts\Repositories\OrderRepositoryInterface;
use Modules\Orders\Exceptions\OrdersException;
use Modules\Orders\Models\Order;
use Modules\Orders\Support\OrderWalletHoldGuard;
use Modules\Wallet\Services\WalletService;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class CancelOrderPaymentAction
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly WalletService $walletService,
    ) {}

    /**
     * Reverse order wallet holds opened on PaymentCompleted. Does not call
     * Payment — gateway refund is deferred (see docs/DEFERRED_ITEMS.md).
     * Idempotent for unpaid / already-reversed holds; refuses settled orders.
     *
     * @throws Throwable
     */
    public function handle(Order $order): Order
    {
        return DB::transaction(function () use ($order) {
            $order = $this->orderRepository->lockForUpdate($order);

            if ($order->wallet_settled_at !== null) {
                throw new OrdersException('you can not cancel this order', Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $order->loadMissing(['user', 'provider', 'acceptedOffer']);

            $gross = (float) $order->price;
            $operation = $order->acceptedOffer ?? $order;

            $userWallet = null;
            $providerWallet = null;

            if ($order->user !== null) {
                $userWallet = $order->user->wallet()->lockForUpdate()->firstOrCreate();
            }

            if ($order->provider !== null) {
                $providerWallet = $order->provider->wallet()->lockForUpdate()->firstOrCreate();
            }

            $shortfall = OrderWalletHoldGuard::cancellationShortfall($order, $userWallet, $providerWallet);

            if ($shortfall !== null) {
                throw new OrdersException('order_wallet_hold_insufficient', Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            if ($order->user !== null && $userWallet !== null) {
                if ((float) $userWallet->pending_debit >= $gross && $gross > 0) {
                    $this->walletService->reversePendingDebit(
                        $order->user,
                        $gross,
                        $operation,
                        "Order#{$order->id} cancelled — pending debit released",
                    );
                }
            }

            if ($order->provider !== null && $providerWallet !== null) {
                if ((float) $providerWallet->pending_credit >= $gross && $gross > 0) {
                    $this->walletService->reversePendingCredit(
                        $order->provider,
                        $gross,
                        $operation,
                        "Order#{$order->id} cancelled — pending credit released",
                    );
                }

                $feeHold = (float) $providerWallet->pending_debit;
                if ($feeHold < 0) {
                    $this->walletService->reversePendingDebit(
                        $order->provider,
                        $feeHold,
                        $operation,
                        "Order#{$order->id} cancelled — fee hold closed",
                    );
                }
            }

            return $order;
        });
    }
}
