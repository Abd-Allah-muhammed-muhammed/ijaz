<?php

namespace Modules\Orders\Support;

use Modules\Orders\Enums\OfferStatusEnum;
use Modules\Orders\Models\Order;
use Modules\Wallet\Models\Wallet;

final class OrderWalletHoldGuard
{
    public static function paidOrderRequiresHoldReversal(Order $order): bool
    {
        return (float) $order->price > 0
            && $order->acceptedOffer !== null
            && $order->acceptedOffer->status === OfferStatusEnum::Paid;
    }

    /**
     * @return array{column: string, required: float, available: float}|null
     */
    public static function settlementShortfall(Order $order, ?Wallet $userWallet, ?Wallet $providerWallet): ?array
    {
        $gross = (float) $order->price;

        if ($userWallet !== null && (float) $userWallet->pending_debit < $gross) {
            return [
                'column' => 'pending_debit',
                'required' => $gross,
                'available' => (float) $userWallet->pending_debit,
            ];
        }

        if ($providerWallet !== null && (float) $providerWallet->pending_credit < $gross) {
            return [
                'column' => 'pending_credit',
                'required' => $gross,
                'available' => (float) $providerWallet->pending_credit,
            ];
        }

        return null;
    }

    /**
     * @return array{column: string, required: float, available: float}|null
     */
    public static function cancellationShortfall(Order $order, ?Wallet $userWallet, ?Wallet $providerWallet): ?array
    {
        if (! self::paidOrderRequiresHoldReversal($order)) {
            return null;
        }

        $gross = (float) $order->price;

        if ($userWallet !== null && (float) $userWallet->pending_debit < $gross) {
            return [
                'column' => 'pending_debit',
                'required' => $gross,
                'available' => (float) $userWallet->pending_debit,
            ];
        }

        if ($providerWallet !== null && (float) $providerWallet->pending_credit < $gross) {
            return [
                'column' => 'pending_credit',
                'required' => $gross,
                'available' => (float) $providerWallet->pending_credit,
            ];
        }

        if ($providerWallet !== null) {
            $expectedFeeHold = -((float) $order->provider_fees);
            $actualFeeHold = (float) $providerWallet->pending_debit;

            if ($expectedFeeHold < 0 && $actualFeeHold > $expectedFeeHold) {
                return [
                    'column' => 'pending_debit',
                    'required' => $expectedFeeHold,
                    'available' => $actualFeeHold,
                ];
            }
        }

        return null;
    }
}
