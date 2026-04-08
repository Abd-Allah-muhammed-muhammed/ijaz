<?php

namespace Lib\Payment\Facade;

use Illuminate\Support\Facades\Facade;
use Lib\Payment\Contracts\IPaymentGate;
use Lib\Payment\DTOs\PaymentResponse;
use Lib\Payment\PaymentManager;

/**
 * Payment Facade
 * This facade provides a static interface to the PaymentManager,
 * allowing easy access to payment functionalities without needing to
 * instantiate the PaymentManager directly.
 *
 * @method static IPaymentGate driver(string $driver = null)
 * @method static PaymentResponse get(string $transactionId)
 * @method static PaymentResponse pay(\App\Models\Payment $payment) :
 * @method static PaymentResponse refund(string $transactionId)
 *
 * @see PaymentManager
 *
 * @mixin PaymentManager
 */
class Payment extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return PaymentManager::class;
    }
}
