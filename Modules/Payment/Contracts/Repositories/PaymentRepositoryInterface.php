<?php

namespace Modules\Payment\Contracts\Repositories;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Modules\Payment\DTOs\PaymentVerifyResult;
use Modules\Payment\Models\Payment;

interface PaymentRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function createForOwner(Model $owner, array $attributes): Payment;

    public function findById(string|int $id): ?Payment;

    public function lockForUpdate(Payment $payment): Payment;

    public function updateFromVerifyResult(Payment $payment, PaymentVerifyResult $result): Payment;

    public function refresh(Payment $payment): Payment;

    public function sumAcceptedAmount(): float|int|string;

    /**
     * @return Collection<string, float|int|string>
     */
    public function acceptedDailyTotalsSince(CarbonInterface $since): Collection;
}
