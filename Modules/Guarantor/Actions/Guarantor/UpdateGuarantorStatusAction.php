<?php

namespace Modules\Guarantor\Actions\Guarantor;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\Guarantor\Actions\Chat\OpenGuarantorChatAction;
use Modules\Guarantor\Contracts\Repositories\GuarantorRepositoryInterface;
use Modules\Guarantor\DTOs\UpdateGuarantorStatusData;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Exceptions\GuarantorException;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Guarantor\Notifications\GuarantorApprovedNotification;
use Modules\Guarantor\Notifications\GuarantorEndedNotification;
use Modules\Guarantor\Notifications\GuarantorRejectedNotification;
use Throwable;

class UpdateGuarantorStatusAction
{
    public function __construct(
        private readonly GuarantorRepositoryInterface $guarantorRepository,
        private readonly LogGuarantorStatusHistoryAction $logStatusHistory,
        private readonly OpenGuarantorChatAction $openGuarantorChatAction,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(
        GuarantorRequest $request,
        UpdateGuarantorStatusData $data,
        Model $actor,
        string $actorRole,
    ): GuarantorRequest {
        return DB::transaction(function () use ($request, $data, $actor, $actorRole) {
            if (! GuarantorStatusEnum::isAllowed($request->status, $data->status, $actorRole)) {
                throw new GuarantorException('guarantor.status_transition_not_allowed', 422);
            }

            $fromStatus = $request->status->value;

            $updateData = [
                'status' => $data->status,
            ];

            if ($data->status === GuarantorStatusEnum::Ended) {
                $updateData['ended_at'] = now();
            }

            if ($data->status === GuarantorStatusEnum::Cancelled) {
                $updateData['cancelled_at'] = now();
                $updateData['cancellation_reason'] = $data->reason;
            }

            if ($data->status === GuarantorStatusEnum::Refunded) {
                $updateData['refunded_at'] = now();
            }

            $guarantorRequest = $this->guarantorRepository->update($request, $updateData);

            $this->logStatusHistory->handle(
                $guarantorRequest,
                $actor,
                $fromStatus,
                $data->status->value,
                $data->reason,
                $data->notes,
            );

            if ($data->status === GuarantorStatusEnum::Approved) {
                $this->openGuarantorChatAction->handle($guarantorRequest);
            }

            match ($data->status) {
                GuarantorStatusEnum::Approved => $guarantorRequest->requester->notify(
                    new GuarantorApprovedNotification($guarantorRequest)
                ),
                GuarantorStatusEnum::Rejected => $guarantorRequest->requester->notify(
                    new GuarantorRejectedNotification($guarantorRequest)
                ),
                GuarantorStatusEnum::Ended,
                GuarantorStatusEnum::Cancelled => collect([
                    $guarantorRequest->requester,
                    $guarantorRequest->counterparty,
                ])->each->notify(new GuarantorEndedNotification($guarantorRequest)),
                default => null,
            };

            return $guarantorRequest->load(['requester', 'counterparty', 'installments', 'companyDetail', 'media']);
        });
    }
}
