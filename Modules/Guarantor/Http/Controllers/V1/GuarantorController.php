<?php

namespace Modules\Guarantor\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use MMAE\ApiResponse\Traits\HasApiResponse;
use Modules\Guarantor\DTOs\CompanyDetailData;
use Modules\Guarantor\DTOs\GuarantorData;
use Modules\Guarantor\DTOs\InstallmentData;
use Modules\Guarantor\DTOs\UpdateGuarantorStatusData;
use Modules\Guarantor\Http\Requests\StoreCompanyGuarantorRequest;
use Modules\Guarantor\Http\Requests\StoreIndividualGuarantorRequest;
use Modules\Guarantor\Http\Requests\UpdateGuarantorRequest;
use Modules\Guarantor\Http\Requests\UpdateGuarantorStatusRequest;
use Modules\Guarantor\Http\Resources\Api\GuarantorCollection;
use Modules\Guarantor\Http\Resources\Api\GuarantorResource;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Guarantor\Services\GuarantorService;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

#[Group('Guarantor Requests')]
class GuarantorController extends Controller
{
    use HasApiResponse;

    public function __construct(
        private readonly GuarantorService $service,
    ) {}

    /**
     * List my guarantor requests (as requester or counterparty).
     */
    public function index(Request $request): JsonResponse
    {
        return $this->successResponse(
            GuarantorCollection::make(
                $this->service->listForActor(
                    auth()->user(),
                    $request->integer('per_page', 10)
                )
            )
        );
    }

    /**
     * Create individual guarantor request.
     */
    public function storeIndividual(StoreIndividualGuarantorRequest $request): JsonResponse
    {
        $data = GuarantorData::fromRequest($request);
        $guarantorRequest = $this->service->createIndividual(
            $data,
            auth()->user(),
            $request
        );

        return $this->successResponse(
            GuarantorResource::make($guarantorRequest),
            __('guarantor.created_successfully')
        );
    }

    /**
     * Create company guarantor request.
     */
    public function storeCompany(StoreCompanyGuarantorRequest $request): JsonResponse
    {
        $data = GuarantorData::fromRequest($request);
        $companyData = CompanyDetailData::fromRequest($request);
        $installments = InstallmentData::collectionFromRequest($request);

        $guarantorRequest = $this->service->createCompany(
            $data,
            $companyData,
            $installments,
            auth()->user(),
            $request
        );

        return $this->successResponse(
            GuarantorResource::make($guarantorRequest),
            __('guarantor.created_successfully')
        );
    }

    /**
     * Show guarantor request details.
     */
    public function show(GuarantorRequest $guarantorRequest): JsonResponse
    {
        $this->authorize('view', $guarantorRequest);

        return $this->successResponse(
            GuarantorResource::make(
                $this->service->loadForShow($guarantorRequest)
            )
        );
    }

    /**
     * Update guarantor request (status = New only).
     */
    public function update(
        UpdateGuarantorRequest $request,
        GuarantorRequest $guarantorRequest,
    ): JsonResponse {
        $this->authorize('update', $guarantorRequest);

        $updated = $this->service->update(
            $guarantorRequest,
            $request->validated(),
            $request
        );

        return $this->successResponse(
            GuarantorResource::make($this->service->loadForShow($updated)),
            __('guarantor.updated_successfully')
        );
    }

    /**
     * Delete guarantor request (status = New only).
     */
    public function destroy(GuarantorRequest $guarantorRequest): JsonResponse
    {
        $this->authorize('delete', $guarantorRequest);
        $this->service->delete($guarantorRequest);

        return $this->successMessageResponse(__('guarantor.deleted_successfully'));
    }

    /**
     * Update status (approve / reject / cancel / end).
     */
    public function updateStatus(
        UpdateGuarantorStatusRequest $request,
        GuarantorRequest $guarantorRequest,
    ): JsonResponse {
        $this->authorize('updateStatus', $guarantorRequest);

        $data = UpdateGuarantorStatusData::fromRequest($request);
        $actorRole = $this->service->resolveActorRole($guarantorRequest, auth()->user());

        $updated = $this->service->updateStatus(
            $guarantorRequest,
            $data,
            auth()->user(),
            $actorRole
        );

        return $this->successResponse(
            GuarantorResource::make($this->service->loadForShow($updated)),
            __('guarantor.status_updated_successfully')
        );
    }

    /**
     * Pay individual guarantor request (counterparty only).
     */
    public function pay(GuarantorRequest $guarantorRequest): JsonResponse
    {
        $this->authorize('pay', $guarantorRequest);

        $paymentResponse = $this->service->payIndividual(
            $guarantorRequest,
            auth()->user()
        );

        return $this->successResponse($paymentResponse);
    }

    /**
     * Delete media from guarantor request (status = New only).
     */
    public function deleteMedia(
        GuarantorRequest $guarantorRequest,
        Media $media,
    ): JsonResponse {
        $this->authorize('deleteMedia', $guarantorRequest);
        $this->service->deleteMedia($guarantorRequest, $media);

        return $this->successMessageResponse(__('guarantor.media_deleted_successfully'));
    }
}
