<?php

namespace Modules\Payment\Gateways;

use Modules\Payment\Contracts\PaymentGatewayInterface;
use Modules\Payment\DTOs\PaymentInitResult;
use Modules\Payment\DTOs\PaymentVerifyResult;
use Modules\Payment\Enums\PaymentStatusEnum;
use Modules\Payment\Models\Payment;
use Paytabscom\Laravel_paytabs\paypage;
use Paytabscom\Laravel_paytabs\PaytabsEnum;
use Throwable;

class PayTabsGateway implements PaymentGatewayInterface
{
    public function __construct(
        private readonly paypage $paypage,
    ) {}

    public function getConfig(): array
    {
        $mode = config('payment.drivers.paytabs.mode', 'test');

        return config("payment.drivers.paytabs.{$mode}", []);
    }

    public function initiate(Payment $payment): PaymentInitResult
    {
        try {
            $config = $this->getConfig();
            $page = $this->paypage
                ->sendPaymentCode('all')
                ->sendTransaction('sale', PaytabsEnum::TRAN_CLASS_ECOM)
                ->sendHideShipping(true)
                ->sendCart(
                    $payment->id,
                    $payment->amount,
                    'Payment for '.class_basename($payment->product_type).' #'.$payment->product_id,
                )
                ->sendCustomerDetails(
                    $payment->user->name,
                    $payment->user->email,
                    $payment->user->phone,
                    null, null, null,
                    $config['region'] ?? 'SAU',
                    null,
                    request()->ip(),
                )
                ->sendURLs(
                    $this->redirectUrl($payment),
                    $this->callbackUrl($payment),
                )
                ->sendLanguage(app()->getLocale())
                ->create_pay_page();

            return new PaymentInitResult(
                status: 'success',
                driver: 'paytabs',
                url: $page->getTargetUrl(),
                payable: true,
                transactionId: null,
            );
        } catch (Throwable $e) {
            return new PaymentInitResult(
                status: 'failed',
                driver: 'paytabs',
                url: '',
                payable: false,
                message: $e->getMessage(),
            );
        }
    }

    public function verify(Payment $payment, array $payload): PaymentVerifyResult
    {
        $tranRef = $payload['tranRef'] ?? null;

        if (! $tranRef) {
            return new PaymentVerifyResult(
                status: PaymentStatusEnum::Rejected,
                message: 'Missing tranRef in payload',
            );
        }

        $response = $this->paypage->queryTransaction($tranRef);

        if ($response->failed) {
            return new PaymentVerifyResult(
                status: PaymentStatusEnum::Rejected,
                rawResponse: (array) $response,
                message: $response->message ?? 'Transaction not found',
            );
        }

        $status = match ($response->payment_result->response_status ?? '') {
            'A' => PaymentStatusEnum::Accepted,
            'C' => PaymentStatusEnum::Canceled,
            default => PaymentStatusEnum::Rejected,
        };

        return new PaymentVerifyResult(
            status: $status,
            transactionId: $tranRef,
            rawResponse: (array) $response,
        );
    }

    private function redirectUrl(Payment $payment): string
    {
        return route('payment.redirect', ['driver' => 'paytabs', 'payment' => $payment->id]);
    }

    private function callbackUrl(Payment $payment): string
    {
        return route('payment.callback', ['driver' => 'paytabs', 'payment' => $payment->id]);
    }
}
