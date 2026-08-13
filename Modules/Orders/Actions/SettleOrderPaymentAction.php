<?php

namespace Modules\Orders\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Orders\Contracts\Repositories\OrderRepositoryInterface;
use Modules\Orders\Models\Order;
use Modules\Wallet\Services\WalletService;
use Throwable;

class SettleOrderPaymentAction
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly WalletService $walletService,
    ) {}

    /**
     * Close order wallet holds after the dispute window: reverse the user's
     * pending_debit in full, and release the provider's pending_credit to
     * balance net of provider_fees (fee stays inside pending_credit, matching
     * EndGuarantorAction). Idempotent — a second call is a no-op.
     *
     * @throws Throwable
     */
    public function handle(Order $order): Order
    {
        return DB::transaction(function () use ($order) {
            $order = $this->orderRepository->lockForUpdate($order);

            if ($order->wallet_settled_at !== null) {
                return $order;
            }

            $order->loadMissing(['user', 'provider', 'acceptedOffer']);

            $gross = (float) $order->price;
            $net = $gross - (float) $order->provider_fees;
            $operation = $order->acceptedOffer ?? $order;

            if ($order->user !== null) {
                $userWallet = $order->user->wallet()->lockForUpdate()->firstOrCreate();
                if ((float) $userWallet->pending_debit > 0) {
                    $this->walletService->reversePendingDebit(
                        $order->user,
                        $gross,
                        $operation,
                        "Order#{$order->id} settled — pending debit released",
                    );
                }
            }

            if ($order->provider !== null) {
                $providerWallet = $order->provider->wallet()->lockForUpdate()->firstOrCreate();
                if ((float) $providerWallet->pending_credit > 0) {
                    $this->walletService->releasePendingCreditToBalance(
                        $order->provider,
                        $gross,
                        $net,
                        $operation,
                        "Order#{$order->id} settled — funds released",
                    );
                }
            }

            return $this->orderRepository->update($order, [
                'wallet_settled_at' => now(),
            ]);
        });
    }
}
