<?php

namespace Modules\Guarantor\Actions\Payment;

use App\Enums\Payment\PaymentStatusEnum;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Lib\Payment\Facade\Payment as PaymentFacade;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Exceptions\GuarantorException;
use Modules\Guarantor\Models\GuarantorRequest;
use Throwable;

class PayIndividualGuarantorAction
{
    /**
     * @return array<string, mixed>
     *
     * @throws Throwable
     */
    public function handle(GuarantorRequest $request, Model $actor): array
    {
        return DB::transaction(function () use ($request, $actor) {
            if ($request->status->isNot(GuarantorStatusEnum::Approved)) {
                throw new GuarantorException('guarantor.status_transition_not_allowed', 422);
            }

            $payment = Payment::query()->create([
                'user_id' => $actor->getKey(),
                'user_type' => $actor::class,
                'product_id' => $request->id,
                'product_type' => GuarantorRequest::class,
                'amount' => $request->total,
                'status' => PaymentStatusEnum::Pending,
                'driver' => PaymentFacade::getDefaultDriver(),
            ]);

            // PayTabs redirect/callback URLs resolved in PayTabsGate by product_type
            // route('payment.paytabs.guarantor.redirect', $payment)
            // route('payment.paytabs.guarantor.callback', $payment)
            $paymentResponse = PaymentFacade::pay($payment);

            return $paymentResponse->toArray();
        });
    }
}
