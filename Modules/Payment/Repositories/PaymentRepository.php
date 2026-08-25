<?php

namespace Modules\Payment\Repositories;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Payment\Contracts\Repositories\PaymentRepositoryInterface;
use Modules\Payment\DTOs\PaymentVerifyResult;
use Modules\Payment\Enums\PaymentStatusEnum;
use Modules\Payment\Models\Payment;

class PaymentRepository implements PaymentRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function createForOwner(Model $owner, array $attributes): Payment
    {
        return $owner->payments()->create($attributes);
    }

    public function findById(string|int $id): ?Payment
    {
        return Payment::query()->find($id);
    }

    public function lockForUpdate(Payment $payment): Payment
    {
        /** @var Payment $locked */
        $locked = Payment::query()
            ->whereKey($payment->getKey())
            ->lockForUpdate()
            ->firstOrFail();

        return $locked;
    }

    public function updateFromVerifyResult(Payment $payment, PaymentVerifyResult $result): Payment
    {
        $payment->update([
            'status' => $result->status,
            'transaction_id' => $result->transactionId,
            'response' => $result->rawResponse,
            'message' => $result->message,
        ]);

        return $payment;
    }

    public function refresh(Payment $payment): Payment
    {
        return $payment->refresh();
    }

    public function sumAcceptedAmount(): float|int|string
    {
        return Payment::query()
            ->where('status', '=', PaymentStatusEnum::Accepted)
            ->sum('amount');
    }

    /**
     * @return Collection<string, float|int|string>
     */
    public function acceptedDailyTotalsSince(CarbonInterface $since): Collection
    {
        return Payment::query()
            ->where('status', '=', PaymentStatusEnum::Accepted)
            ->where('created_at', '>=', $since)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('sum(amount) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('total', 'date');
    }
}
