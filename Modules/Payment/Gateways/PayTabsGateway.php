<?php

namespace Modules\Payment\Gateways;

use Modules\Payment\Contracts\PaymentGatewayInterface;
use Modules\Payment\DTOs\PaymentInitResult;
use Modules\Payment\DTOs\PaymentVerifyResult;
use Modules\Payment\Enums\PaymentStatusEnum;
use Modules\Payment\Models\Payment;
use Paytabscom\Laravel_paytabs\paypage;
use Paytabscom\Laravel_paytabs\PaytabsApi;
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
                    config('paytabs.region', 'SAU'),
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
        $isIpn = isset($payload['tran_ref']) && ! isset($payload['tranRef']);

        if ($isIpn) {
            return $this->verifyIpn($payment, $payload);
        }

        return $this->verifyReturnUrl($payment, $payload);
    }

    private function verifyReturnUrl(Payment $payment, array $payload): PaymentVerifyResult
    {
        $api = $this->paytabsApi();

        if (! $api->is_valid_redirect($payload)) {
            return new PaymentVerifyResult(
                status: PaymentStatusEnum::Rejected,
                message: 'Invalid signature on return URL',
            );
        }

        $respStatus = $payload['respStatus'] ?? '';
        $tranRef = $payload['tranRef'] ?? null;

        $status = $this->mapResponseStatus($respStatus);

        if ($tranRef) {
            $response = $this->paypage->queryTransaction($tranRef);

            if (! $response->failed) {
                $status = $this->mapResponseStatus($response->payment_result->response_status ?? '');
            }
        }

        return new PaymentVerifyResult(
            status: $status,
            transactionId: $tranRef,
            rawResponse: $payload,
        );
    }

    private function verifyIpn(Payment $payment, array $payload): PaymentVerifyResult
    {
        $rawBody = request()->getContent();
        $signature = request()->header('signature', '');

        $api = $this->paytabsApi();

        if (! empty($signature) && ! $api->is_valid_ipn($rawBody, $signature)) {
            return new PaymentVerifyResult(
                status: PaymentStatusEnum::Rejected,
                message: 'Invalid signature on IPN',
            );
        }

        $tranRef = $payload['tran_ref'] ?? null;
        $responseStatus = $payload['payment_result']['response_status'] ?? '';

        $status = $this->mapResponseStatus($responseStatus);

        return new PaymentVerifyResult(
            status: $status,
            transactionId: $tranRef,
            rawResponse: $payload,
        );
    }

    private function paytabsApi(): PaytabsApi
    {
        return PaytabsApi::getInstance(
            config('paytabs.region'),
            config('paytabs.profile_id'),
            config('paytabs.server_key'),
        );
    }

    private function mapResponseStatus(string $responseStatus): PaymentStatusEnum
    {
        return match ($responseStatus) {
            'A' => PaymentStatusEnum::Accepted,
            'C', 'V' => PaymentStatusEnum::Canceled,
            default => PaymentStatusEnum::Rejected,
        };
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
