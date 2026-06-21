<?php

namespace Modules\Guarantor\Actions\Payment;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Exceptions\GuarantorException;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Payment\Services\PaymentService;
use Throwable;

class PayIndividualGuarantorAction
{
    public function __construct(
        private readonly PaymentService $paymentService,
    ) {}

    /**
     * @return array<string, mixed>
     *
     * @throws Throwable
     */
    public function handle(GuarantorRequest $request, Model $actor): array
    {
        return DB::transaction(function () use ($request, $actor) {
            if ($request->status->isNot(GuarantorStatusEnum::Accepted)) {
                throw new GuarantorException('guarantor.status_transition_not_allowed', 422);
            }

            $result = $this->paymentService->initiate(
                owner: $actor,
                product: $request,
                amount: $request->total,
            );

            return $result->toArray();
        });
    }
}
