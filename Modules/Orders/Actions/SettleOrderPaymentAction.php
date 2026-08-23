<?php

namespace Modules\Orders\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Orders\Contracts\Repositories\OrderRepositoryInterface;
use Modules\Orders\Models\Order;
use Modules\Orders\Support\OrderWalletHoldGuard;
use Modules\Wallet\Models\Wallet;
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
     * pending_debit in full, release the provider's pending_credit to balance
     * net of provider_fees (fee stays inside pending_credit, matching
     * EndGuarantorAction), and zero the provider's leftover negative
     * pending_debit from AdjustPendingAction. Idempotent — a second call is a no-op.
     * Skips (does not stamp wallet_settled_at) when a wallet cannot cover order.price
     * without driving pending_credit / pending_debit negative.
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

            $userWallet = null;
            $providerWallet = null;

            if ($order->user !== null) {
                $userWallet = $order->user->wallet()->lockForUpdate()->firstOrCreate();
            }

            if ($order->provider !== null) {
                $providerWallet = $order->provider->wallet()->lockForUpdate()->firstOrCreate();
            }

            $shortfall = OrderWalletHoldGuard::settlementShortfall($order, $userWallet, $providerWallet);

            if ($shortfall !== null) {
                $wallet = $shortfall['column'] === 'pending_debit' ? $userWallet : $providerWallet;

                if ($wallet !== null) {
                    $this->logInsufficientHold(
                        $order,
                        $wallet,
                        $shortfall['column'],
                        $shortfall['required'],
                        $shortfall['available'],
                    );
                }

                return $order;
            }

            if ($order->user !== null && $userWallet !== null && (float) $userWallet->pending_debit > 0) {
                $this->walletService->reversePendingDebit(
                    $order->user,
                    $gross,
                    $operation,
                    "Order#{$order->id} settled — pending debit released",
                );
            }

            if ($order->provider !== null && $providerWallet !== null) {
                if ((float) $providerWallet->pending_credit > 0) {
                    $this->walletService->releasePendingCreditToBalance(
                        $order->provider,
                        $gross,
                        $net,
                        $operation,
                        "Order#{$order->id} settled — funds released",
                    );
                }

                // AdjustPendingAction subtracts debitDelta; payment left a negative
                // fee residual. reversePendingDebit($leftover) increments it to 0.
                $feeHold = (float) $providerWallet->fresh()->pending_debit;
                if ($feeHold < 0) {
                    $this->walletService->reversePendingDebit(
                        $order->provider,
                        $feeHold,
                        $operation,
                        "Order#{$order->id} settled — fee hold closed",
                    );
                }
            }

            return $this->orderRepository->update($order, [
                'wallet_settled_at' => now(),
            ]);
        });
    }

    private function logInsufficientHold(
        Order $order,
        Wallet $wallet,
        string $column,
        float $required,
        float $available,
    ): void {
        Log::warning('Order settlement skipped: insufficient pending hold', [
            'order_id' => $order->id,
            'wallet_id' => $wallet->id,
            'column' => $column,
            'required' => $required,
            'available' => $available,
            'shortfall' => $required - $available,
        ]);
    }
}
