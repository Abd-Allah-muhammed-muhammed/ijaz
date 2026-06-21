<?php

namespace App\Actions\Payment\Test;

use Modules\Payment\Enums\PaymentStatusEnum;
use Modules\Payment\Models\Payment;
use Closure;
use http\Exception\RuntimeException;
use Illuminate\Http\Request;

class UpdatePaymentStatus
{
    public function __construct(protected Request $request) {}

    public function __invoke(Payment $payment, Closure $next)
    {
        $status = $this->request->input('status');
        if (! $status) {

        }
        if (in_array($status, ['success', 'completed', 'accepted'])) {
            $payment->status = PaymentStatusEnum::Accepted;
        } elseif (in_array($status, ['failed', 'rejected', 'error'])) {
            $payment->status = PaymentStatusEnum::Rejected;
        } elseif ($status === 'cancelled') {
            $payment->status = PaymentStatusEnum::Canceled;
        } else {
            throw new RuntimeException('Unsupported payment status: '.$status);
        }
        $payment->update([
            'driver' => \Lib\Payment\Facade\Payment::getDefaultDriver(),
            'transaction_id' => $this->request->input('payment_id'),
            'request' => $this->request->all(),
            'response' => \Lib\Payment\Facade\Payment::get($this->request->input('payment_id'))->toArray(),
        ]);

        $payment->save();

        return $next($payment);

    }
}
