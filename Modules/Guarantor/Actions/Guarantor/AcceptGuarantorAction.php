<?php

namespace Modules\Guarantor\Actions\Guarantor;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\Guarantor\Actions\Chat\OpenGuarantorChatAction;
use Modules\Guarantor\Contracts\Repositories\GuarantorRepositoryInterface;
use Modules\Guarantor\DTOs\GuarantorAcceptUploadData;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Enums\GuarantorTypeEnum;
use Modules\Guarantor\Exceptions\GuarantorException;
use Modules\Guarantor\Models\GuarantorCompanyDetail;
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
        GuarantorAcceptUploadData $uploads,
    ): GuarantorRequest {
        return DB::transaction(function () use ($request, $actor, $uploads) {
            $request = $this->guarantorRepository->findForUpdate($request);

            $this->assertActorIsCounterparty($request, $actor);

            if (! $request->status->is(GuarantorStatusEnum::ApprovedByAdmin)) {
                throw new GuarantorException('guarantor.accept_not_allowed', 422);
            }

            $fromStatus = $request->status->value;

            $guarantorRequest = $this->guarantorRepository->update($request, [
                'status' => GuarantorStatusEnum::Accepted,
            ]);

            $guarantorRequest->addMedia($uploads->signature)
                ->toMediaCollection('counterparty_signature');

            if ($guarantorRequest->type === GuarantorTypeEnum::Company) {
                $this->attachCounterpartyDocuments($guarantorRequest, $uploads);
            }

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
                'companyDetail.media',
                'media',
                'statusHistories',
            ]);
        });
    }

    private function attachCounterpartyDocuments(
        GuarantorRequest $guarantorRequest,
        GuarantorAcceptUploadData $uploads,
    ): void {
        $guarantorRequest->loadMissing('companyDetail');

        $companyDetail = $guarantorRequest->companyDetail;

        if (! $companyDetail instanceof GuarantorCompanyDetail) {
            return;
        }

        if ($uploads->ibanCertificate !== null) {
            $companyDetail->addMedia($uploads->ibanCertificate)
                ->toMediaCollection('counterparty_iban_certificate');
        }

        if ($uploads->crFile !== null) {
            $companyDetail->addMedia($uploads->crFile)
                ->toMediaCollection('counterparty_cr_file');
        }

        if ($uploads->articlesOfAssociation !== null) {
            $companyDetail->addMedia($uploads->articlesOfAssociation)
                ->toMediaCollection('counterparty_articles_of_association');
        }

        if ($uploads->nationalAddressFile !== null) {
            $companyDetail->addMedia($uploads->nationalAddressFile)
                ->toMediaCollection('counterparty_national_address_file');
        }
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
