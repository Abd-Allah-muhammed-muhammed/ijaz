<?php

namespace Modules\Payment\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Payment\Enums\PaymentStatusEnum;
use Modules\Payment\Models\Payment;
use Modules\Payment\Services\PaymentService;

class PaymentCallbackController extends Controller
{
    public function __construct(
        private readonly PaymentService $paymentService,
    ) {}

    /**
     * Return URL — user is redirected here after payment page.
     * GET|POST /payments/{driver}/{payment}/redirect
     */
    public function redirect(string $driver, Payment $payment, Request $request): RedirectResponse
    {
        abort_if($payment->driver !== $driver, 404);

        $payment = $this->paymentService->handleCallback($payment, $request->all());

        return match ($payment->status) {
            PaymentStatusEnum::Accepted => redirect()->route('payment.success', $payment),
            default => redirect()->route('payment.failed', $payment),
        };
    }

    /**
     * Webhook/IPN — server-to-server callback from gateway.
     * GET|POST /payments/{driver}/{payment}/callback
     */
    public function callback(string $driver, Payment $payment, Request $request): Response
    {
        abort_if($payment->driver !== $driver, 404);

        $this->paymentService->handleCallback($payment, $request->all());

        return response('OK', 200);
    }
}
