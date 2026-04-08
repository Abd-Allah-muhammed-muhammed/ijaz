<?php

namespace App\Http\Controllers\Payments;

use App\Actions\Payment\AddModelTransaction;
use App\Actions\Payment\GuaranteeRequest\AddProviderTransactionForGuaranteeRequest;
use App\Actions\Payment\GuaranteeRequest\AddUserTransactionForGuaranteeRequest;
use App\Actions\Payment\GuaranteeRequest\NotifyProviderForGuaranteeRequest;
use App\Actions\Payment\GuaranteeRequest\NotifyUserForGuaranteeRequest;
use App\Actions\Payment\GuaranteeRequest\ProcessGuaranteeRequest;
use App\Actions\Payment\NotifyModelForOp;
use App\Actions\Payment\Order\AddProviderTransaction;
use App\Actions\Payment\Order\AddUserTransaction;
use App\Actions\Payment\Order\NotifyProviderForOrder;
use App\Actions\Payment\Order\NotifyUserForOrder;
use App\Actions\Payment\Order\ProcessOrder;
use App\Actions\Payment\PayTabs\UpdatePaymentStatus;
use App\Actions\Payment\TopUp\ProcessToUpRequest;
use App\Enums\Payment\PaymentStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\GuaranteeRequest;
use App\Models\OrderOffer;
use App\Models\Payment;
use App\Models\TopUpRequest;
use http\Exception\RuntimeException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Pipeline;
use Throwable;

class PayTabsController extends Controller
{
    /**
     * @throws Throwable
     */
    public function redirect(Payment $payment, Request $request): RedirectResponse
    {
        assert($request->input('cartId') === $payment->id, 'Payment ID mismatch');

        return match ($payment->product_type) {
            OrderOffer::class => $this->offerRequestPayment($request, $payment),
            TopUpRequest::class => $this->TopUpPayment($request, $payment),
            GuaranteeRequest::class => $this->GuaranteeRequestPayment($request, $payment),
            default => throw new RuntimeException('Unknown product type: '.$payment->product_type),
        };
    }

    public function offerRequestPayment(Request $request, Payment $payment): RedirectResponse
    {
        try {
            $payment = Pipeline::send($payment)
                ->withinTransaction()
                ->through([
                    new UpdatePaymentStatus($request),
                    ProcessOrder::class,
                    AddProviderTransaction::class,
                    AddUserTransaction::class,
                    NotifyProviderForOrder::class,
                    NotifyUserForOrder::class,
                ])
                ->thenReturn();

            return match ($payment->status) {
                PaymentStatusEnum::Accepted => redirect()->route('payment.paytabs.success', ['payment' => $payment->id]),
                PaymentStatusEnum::Rejected, PaymentStatusEnum::Canceled->value => redirect()->route('payment.paytabs.failed', ['payment' => $payment->id]),
                default => throw new RuntimeException('Unsupported payment status: '.$payment->status, 500),
            };

        } catch (Throwable $e) {
            report($e);

            return redirect()->route('payment.paytabs.failed', ['payment' => $payment->id]);
        }
    }

    public function TopUpPayment(Request $request, Payment $payment): RedirectResponse
    {
        try {
            $payment = Pipeline::send($payment)
                ->withinTransaction()
                ->through([
                    new UpdatePaymentStatus($request),
                    ProcessToUpRequest::class,
                    AddModelTransaction::class,
                    NotifyModelForOp::class,
                ])
                ->thenReturn();

            return match ($payment->status) {
                PaymentStatusEnum::Accepted => redirect()->route('payment.paytabs.success', ['payment' => $payment->id]),
                PaymentStatusEnum::Rejected, PaymentStatusEnum::Canceled->value => redirect()->route('payment.paytabs.failed', ['payment' => $payment->id]),
                default => throw new RuntimeException('Unsupported payment status: '.$payment->status, 500),
            };

        } catch (Throwable $e) {
            report($e);

            return redirect()->route('payment.paytabs.failed', ['payment' => $payment->id]);
        }
    }

    public function GuaranteeRequestPayment(Request $request, Payment $payment): RedirectResponse
    {
        try {
            $payment = Pipeline::send($payment)
                ->withinTransaction()
                ->through([
                    new UpdatePaymentStatus($request),
                    ProcessGuaranteeRequest::class,
                    AddProviderTransactionForGuaranteeRequest::class,
                    AddUserTransactionForGuaranteeRequest::class,
                    NotifyProviderForGuaranteeRequest::class,
                    NotifyUserForGuaranteeRequest::class,
                ])
                ->thenReturn();

            return match ($payment->status) {
                PaymentStatusEnum::Accepted => redirect()->route('payment.paytabs.success', ['payment' => $payment->id]),
                PaymentStatusEnum::Rejected, PaymentStatusEnum::Canceled->value => redirect()->route('payment.paytabs.failed', ['payment' => $payment->id]),
                default => throw new RuntimeException('Unsupported payment status: '.$payment->status, 500),
            };

        } catch (Throwable $e) {
            report($e);

            return redirect()->route('payment.paytabs.failed', ['payment' => $payment->id]);
        }
    }

    public function callback(Payment $payment, Request $request)
    {
        dd($payment, $request);
    }

    public function success()
    {
        return view('payment.success');
    }

    public function failed()
    {
        return view('payment.failed');
    }
}
