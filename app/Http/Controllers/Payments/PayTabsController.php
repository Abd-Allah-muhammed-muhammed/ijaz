<?php

namespace App\Http\Controllers\Payments;

use App\Actions\Payment\AddModelTransaction;
use App\Actions\Payment\NotifyModelForOp;
use App\Actions\Payment\Order\AddProviderTransaction;
use App\Actions\Payment\Order\AddUserTransaction;
use App\Actions\Payment\Order\NotifyProviderForOrder;
use App\Actions\Payment\Order\NotifyUserForOrder;
use App\Actions\Payment\Order\ProcessOrder;
use App\Actions\Payment\PayTabs\UpdatePaymentStatus;
use App\Actions\Payment\TopUp\ProcessToUpRequest;
use Modules\Payment\Enums\PaymentStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\OrderOffer;
use Modules\Payment\Models\Payment;
use http\Exception\RuntimeException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Pipeline;
use Modules\Guarantor\Actions\Payment\AddCounterpartyWalletTransaction;
use Modules\Guarantor\Actions\Payment\AddRequesterWalletTransaction;
use Modules\Guarantor\Actions\Payment\NotifyGuarantorPayment;
use Modules\Guarantor\Actions\Payment\ProcessGuarantorPayment;
use Modules\Guarantor\Models\GuarantorInstallment;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Wallet\Models\TopUpRequest;
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
            GuarantorRequest::class, GuarantorInstallment::class => $this->guarantorPayment($payment, $request),
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

    public function guarantorPayment(Payment $payment, Request $request): RedirectResponse
    {
        assert($request->input('cartId') === $payment->id, 'Payment ID mismatch');

        try {
            $payment = Pipeline::send($payment)
                ->withinTransaction()
                ->through([
                    new UpdatePaymentStatus($request),
                    ProcessGuarantorPayment::class,
                    AddCounterpartyWalletTransaction::class,
                    AddRequesterWalletTransaction::class,
                    NotifyGuarantorPayment::class,
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

    public function callback(Payment $payment, Request $request): RedirectResponse
    {
        return $this->redirect($payment, $request);
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
