<?php

namespace Lib\Payment\Gates;

use Modules\Payment\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Lib\Payment\Contracts\IPaymentGate;
use Lib\Payment\DTOs\PaymentResponse;
use Modules\Guarantor\Models\GuarantorInstallment;
use Modules\Guarantor\Models\GuarantorRequest;
use Paytabscom\Laravel_paytabs\paypage;
use Paytabscom\Laravel_paytabs\PaytabsEnum;

readonly class PayTabsGate implements IPaymentGate
{
    public function __construct(protected paypage $paypage) {}

    public function pay(Payment $payment): PaymentResponse
    {

        /**
         * @var RedirectResponse $page
         */
        $page = $this->paypage->sendPaymentCode('all')
            ->sendTransaction('sale', PaytabsEnum::TRAN_CLASS_ECOM)
            ->sendHideShipping(true)
            ->sendCart(
                $payment->id,
                $payment->amount,
                'pay for '.str($payment->product_type)->afterLast('\\').'#('.$payment->product_id.')'
            )
            ->sendCustomerDetails(
                $payment->user->name, $payment->user->email,
                $payment->user->phone, null, null, null, 'SAU', null,
                request()->ip(),
            )
            ->sendURLs($this->redirectUrl($payment), $this->callbackUrl($payment))
            ->sendLanguage(app()->getLocale())
            ->create_pay_page();

        return new PaymentResponse(
            status: 'success',
            transactionId: '',
            driver: 'paytabs',
            url: $page->getTargetUrl(),
            payable: true,

        );
    }

    public function get(string $transactionId): PaymentResponse
    {
        $payment = $this->paypage->queryTransaction($transactionId);
        if ($payment->failed) {
            return new PaymentResponse(
                status: 'not_found',
                transactionId: $transactionId,
                driver: 'paytabs',
                url: '',
                payable: false,
                data: (array) $payment,
                message: $payment->message
            );
        }

        return new PaymentResponse(
            status: $payment->payment_result->response_status === 'A' ? 'success' : 'failed',
            transactionId: $transactionId,
            driver: 'paytabs',
            url: '',
            payable: false,
            data: (array) $payment,
        );
    }

    public function refund(string $transactionId): PaymentResponse
    {
        // TODO: Implement refund() method.
    }

    private function redirectUrl(Payment $payment): string
    {
        if ($this->isGuarantorPayment($payment)) {
            return route('payment.paytabs.guarantor.redirect', $payment);
        }

        return route('payment.paytabs.redirect', $payment);
    }

    private function callbackUrl(Payment $payment): string
    {
        if ($this->isGuarantorPayment($payment)) {
            return route('payment.paytabs.guarantor.callback', $payment);
        }

        return route('payment.paytabs.callback', $payment);
    }

    private function isGuarantorPayment(Payment $payment): bool
    {
        return in_array($payment->product_type, [
            GuarantorRequest::class,
            GuarantorInstallment::class,
        ], true);
    }
}
