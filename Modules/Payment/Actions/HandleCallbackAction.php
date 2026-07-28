<?php

namespace Modules\Payment\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Payment\Contracts\Repositories\PaymentRepositoryInterface;
use Modules\Payment\Enums\PaymentStatusEnum;
use Modules\Payment\Events\PaymentCompleted;
use Modules\Payment\Events\PaymentFailed;
use Modules\Payment\Models\Payment;
use Modules\Payment\Services\PaymentService;

class HandleCallbackAction
{
    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly PaymentRepositoryInterface $paymentRepository,
    ) {}

    public function handle(Payment $payment, array $payload): void
    {
        if ($payment->status !== PaymentStatusEnum::Pending) {
            return;
        }

        $gateway = $this->paymentService->resolveGateway($payment->driver);
        $result = $gateway->verify($payment, $payload);

        DB::transaction(function () use ($payment, $result) {
            $this->paymentRepository->updateFromVerifyResult($payment, $result);
        });

        DB::afterCommit(function () use ($payment, $result) {
            $this->paymentRepository->refresh($payment);

            if ($result->isAccepted()) {
                event(new PaymentCompleted($payment));
            } else {
                event(new PaymentFailed($payment));
            }
        });
    }
}
