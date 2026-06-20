<?php

namespace Modules\Guarantor\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Modules\Guarantor\Actions\Guarantor\CancelGuarantorAction;
use Modules\Guarantor\Actions\Guarantor\CreateCompanyGuarantorAction;
use Modules\Guarantor\Actions\Guarantor\CreateIndividualGuarantorAction;
use Modules\Guarantor\Actions\Guarantor\DeleteGuarantorAction;
use Modules\Guarantor\Actions\Guarantor\DeleteGuarantorMediaAction;
use Modules\Guarantor\Actions\Guarantor\EndGuarantorAction;
use Modules\Guarantor\Actions\Guarantor\UpdateGuarantorAction;
use Modules\Guarantor\Actions\Guarantor\UpdateGuarantorStatusAction;
use Modules\Guarantor\Actions\Payment\PayIndividualGuarantorAction;
use Modules\Guarantor\Contracts\Repositories\GuarantorRepositoryInterface;
use Modules\Guarantor\DTOs\CompanyDetailData;
use Modules\Guarantor\DTOs\GuarantorData;
use Modules\Guarantor\DTOs\GuarantorFiltersData;
use Modules\Guarantor\DTOs\InstallmentData;
use Modules\Guarantor\DTOs\UpdateGuarantorStatusData;
use Modules\Guarantor\Exceptions\GuarantorException;
use Modules\Guarantor\Models\GuarantorRequest;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Throwable;

class GuarantorService
{
    public function __construct(
        private readonly GuarantorRepositoryInterface $repository,
        private readonly CreateIndividualGuarantorAction $createIndividualAction,
        private readonly CreateCompanyGuarantorAction $createCompanyAction,
        private readonly UpdateGuarantorAction $updateAction,
        private readonly DeleteGuarantorAction $deleteAction,
        private readonly UpdateGuarantorStatusAction $updateStatusAction,
        private readonly DeleteGuarantorMediaAction $deleteMediaAction,
        private readonly PayIndividualGuarantorAction $payIndividualAction,
        private readonly EndGuarantorAction $endAction,
        private readonly CancelGuarantorAction $cancelAction,
    ) {}

    /**
     * @throws Throwable
     */
    public function createIndividual(
        GuarantorData $data,
        Model $requester,
        Request $request,
    ): GuarantorRequest {
        return $this->createIndividualAction->handle($data, $requester, $request);
    }

    /**
     * @param  InstallmentData[]  $installments
     *
     * @throws Throwable
     */
    public function createCompany(
        GuarantorData $data,
        CompanyDetailData $companyData,
        array $installments,
        Model $requester,
        Request $request,
    ): GuarantorRequest {
        return $this->createCompanyAction->handle(
            $data,
            $companyData,
            $installments,
            $requester,
            $request,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws Throwable
     */
    public function update(
        GuarantorRequest $request,
        array $data,
        Request $httpRequest,
    ): GuarantorRequest {
        return $this->updateAction->handle($request, $data, $httpRequest);
    }

    /**
     * @throws Throwable
     */
    public function delete(GuarantorRequest $request): void
    {
        $this->deleteAction->handle($request);
    }

    /**
     * @throws Throwable
     */
    public function updateStatus(
        GuarantorRequest $request,
        UpdateGuarantorStatusData $data,
        Model $actor,
        string $actorRole,
    ): GuarantorRequest {
        return $this->updateStatusAction->handle($request, $data, $actor, $actorRole);
    }

    /**
     * @throws Throwable
     */
    public function deleteMedia(GuarantorRequest $request, Media $media): void
    {
        $this->deleteMediaAction->handle($request, $media);
    }

    /**
     * @return array<string, mixed>
     *
     * @throws Throwable
     */
    public function payIndividual(
        GuarantorRequest $request,
        Model $actor,
    ): array {
        return $this->payIndividualAction->handle($request, $actor);
    }

    /**
     * @throws Throwable
     */
    public function end(
        GuarantorRequest $request,
        Model $actor,
        string $actorRole,
    ): void {
        $this->endAction->handle($request, $actor, $actorRole);
    }

    /**
     * @throws Throwable
     */
    public function cancel(
        GuarantorRequest $request,
        string $reason,
        Model $actor,
        string $actorRole,
    ): void {
        $this->cancelAction->handle($request, $reason, $actor, $actorRole);
    }

    public function loadForShow(GuarantorRequest $request): GuarantorRequest
    {
        return $request->load([
            'requester',
            'counterparty',
            'installments',
            'companyDetail.region',
            'companyDetail.city',
            'companyDetail.media',
            'statusHistories.actor',
            'conversation',
            'media',
        ]);
    }

    public function listForActor(Model $actor, GuarantorFiltersData $filters): LengthAwarePaginator
    {
        return $this->repository->listForActor($actor, $filters);
    }

    public function listAll(int $perPage = 10): LengthAwarePaginator
    {
        return $this->repository->listAll($perPage);
    }

    public function resolveActorRole(GuarantorRequest $request, Model $actor): string
    {
        if (
            $request->requester_type === $actor::class
            && (string) $request->requester_id === (string) $actor->getKey()
        ) {
            return 'requester';
        }

        if (
            $request->counterparty_type === $actor::class
            && (string) $request->counterparty_id === (string) $actor->getKey()
        ) {
            return 'counterparty';
        }

        throw new GuarantorException('guarantor.unauthorized', 403);
    }
}
