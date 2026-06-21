<?php

namespace App\Actions\Payment\PayTabs;

use Modules\Payment\Enums\PaymentStatusEnum;
use Modules\Payment\Models\Payment;
use Closure;
use Illuminate\Http\Request;
use RuntimeException;

class UpdatePaymentStatus
{
    public function __construct(protected Request $request) {}

    public function __invoke(Payment $payment, Closure $next)
    {
        $gateway = \Lib\Payment\Facade\Payment::driver('paytabs');
        $gatewayResponse = $gateway->get($this->request->input('tranRef'));
        $status = $gatewayResponse->getStatus();
        if ($status === 'success') {
            $payment->status = PaymentStatusEnum::Accepted;
        } elseif ($status === 'failed') {
            $payment->status = PaymentStatusEnum::Rejected;
        } elseif ($status === 'cancelled') {
            $payment->status = PaymentStatusEnum::Canceled;
        } else {
            throw new RuntimeException('Unsupported payment status: '.$status);
        }
        $payment->update([
            'driver' => 'paytabs',
            'transaction_id' => $gatewayResponse->getTransactionId(),
            'request' => $this->request->all(),
            'response' => $gatewayResponse->getData(),
        ]);

        return $next($payment);

    }
}
