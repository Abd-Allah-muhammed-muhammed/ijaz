<?php

namespace Modules\Guarantor\Actions\Guarantor;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Modules\Guarantor\Actions\Chat\OpenGuarantorChatAction;
use Modules\Guarantor\Contracts\Repositories\GuarantorRepositoryInterface;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Exceptions\GuarantorException;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Guarantor\Notifications\GuarantorAcceptedNotification;
use Throwable;

class AcceptGuarantorAction
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
        Model $actor,
        UploadedFile $signature,
    ): GuarantorRequest {
        return DB::transaction(function () use ($request, $actor, $signature) {
            $request = $this->guarantorRepository->findForUpdate($request);

            $this->assertActorIsCounterparty($request, $actor);

            if (! $request->status->is(GuarantorStatusEnum::ApprovedByAdmin)) {
                throw new GuarantorException('guarantor.accept_not_allowed', 422);
            }

            $fromStatus = $request->status->value;

            $guarantorRequest = $this->guarantorRepository->update($request, [
                'status' => GuarantorStatusEnum::Accepted,
            ]);

            $guarantorRequest->addMedia($signature)
                ->toMediaCollection('counterparty_signature');

            $this->logStatusHistory->handle(
                $guarantorRequest,
                $actor,
                $fromStatus,
                GuarantorStatusEnum::Accepted->value,
            );

            $this->openGuarantorChatAction->handle($guarantorRequest, $actor);

            $guarantorRequest->loadMissing('requester');
            $guarantorRequest->requester->notify(
                new GuarantorAcceptedNotification($guarantorRequest)
            );

            return $guarantorRequest->load([
                'requester',
                'counterparty',
                'installments',
                'companyDetail',
                'media',
                'statusHistories',
            ]);
        });
    }

    private function assertActorIsCounterparty(GuarantorRequest $request, Model $actor): void
    {
        if (
            $request->counterparty_type === $actor::class
            && (string) $request->counterparty_id === (string) $actor->getKey()
        ) {
            return;
        }

        throw new GuarantorException('guarantor.unauthorized', 403);
    }
}
