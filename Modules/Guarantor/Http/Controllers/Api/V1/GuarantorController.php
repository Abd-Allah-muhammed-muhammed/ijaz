<?php

namespace Modules\Guarantor\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use MMAE\ApiResponse\Traits\HasApiResponse;
use Modules\Guarantor\DTOs\CompanyDetailData;
use Modules\Guarantor\DTOs\GuarantorAcceptUploadData;
use Modules\Guarantor\DTOs\GuarantorData;
use Modules\Guarantor\DTOs\GuarantorFiltersData;
use Modules\Guarantor\DTOs\GuarantorUploadData;
use Modules\Guarantor\DTOs\InstallmentData;
use Modules\Guarantor\Http\Requests\AcceptGuarantorRequest;
use Modules\Guarantor\Http\Requests\OpenGuarantorDisputeRequest;
use Modules\Guarantor\Http\Requests\RejectEndRequestRequest;
use Modules\Guarantor\Http\Requests\RejectGuarantorRequest;
use Modules\Guarantor\Http\Requests\StoreCompanyGuarantorRequest;
use Modules\Guarantor\Http\Requests\StoreIndividualGuarantorRequest;
use Modules\Guarantor\Http\Requests\UpdateGuarantorRequest;
use Modules\Guarantor\Http\Requests\WithdrawGuarantorRequest;
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
     * List my guarantor requests.
     *
     * Returns a paginated list of guarantor requests where the authenticated user
     * is the requester or counterparty.
     *
     * @authenticated
     *
     * @queryParam per_page int Results per page. Example: 10
     * @queryParam status string Filter by status. Example: pending_admin
     *
     * @response 200 {
     *   "status": true,
     *   "data": {
     *     "items": [
     *       {
     *         "id": "01234567-89ab-cdef-0123-456789abcdef",
     *         "type": { "value": "individual", "label": "Individual", "color": "#3b82f6" },
     *         "status": { "value": "pending_admin", "label": "Pending Admin Review", "color": "#f59e0b" },
     *         "title": "Software development guarantee",
     *         "description": "Guarantee for a 3-month project",
     *         "amount": 5000.00,
     *         "fees": 10.00,
     *         "total": 5010.00,
     *         "requester": { "id": "...", "name": "Ahmed Mohamed", "type": "user" },
     *         "counterparty": { "id": "...", "name": "Ali Hassan", "type": "user" },
     *         "installments_count": 0,
     *         "media": [],
     *         "created_at": "2026-06-01T10:00:00+00:00"
     *       }
     *     ],
     *     "total": 25,
     *     "count": 10,
     *     "per_page": 10,
     *     "current_page": 1,
     *     "last_page": 3,
     *     "has_more_pages": true
     *   }
     * }
     * @response 401 { "success": false, "message": "Unauthenticated." }
     */
    public function index(Request $request): JsonResponse
    {
        return $this->successResponse(
            GuarantorCollection::make(
                $this->service->listForActor(
                    auth()->user(),
                    GuarantorFiltersData::fromRequest($request)
                )
            )
        );
    }

    /**
     * Create an individual guarantor request.
     *
     * @authenticated
     *
     * @bodyParam counterparty_phone string required Counterparty phone number. Example: 0501234567
     * @bodyParam amount number required Request amount. Example: 5000
     * @bodyParam title string required Request title. Example: Project guarantee
     * @bodyParam description string required Request description.
     * @bodyParam signature file required Signature file (jpg, jpeg, png, pdf; max 5MB).
     *
     * @response 200 {
     *   "status": true,
     *   "message": "Guarantor request created successfully",
     *   "data": {
     *     "id": "01234567-89ab-cdef-0123-456789abcdef",
     *     "type": { "value": "individual", "label": "Individual", "color": "#3b82f6" },
     *     "status": { "value": "pending_admin", "label": "Pending Admin Review", "color": "#f59e0b" },
     *     "title": "Project guarantee",
     *     "amount": 5000.00,
     *     "fees": 10.00,
     *     "total": 5010.00,
     *     "media": [{ "uuid": "...", "url": "...", "mime_type": "application/pdf" }]
     *   }
     * }
     * @response 401 { "success": false, "message": "Unauthenticated." }
     * @response 403 { "success": false, "message": "You are not authorized to perform this action" }
     * @response 422 { "success": false, "message": "The counterparty phone number is not registered in the system" }
     */
    public function storeIndividual(StoreIndividualGuarantorRequest $request): JsonResponse
    {
        $data = GuarantorData::fromRequest($request);
        $guarantorRequest = $this->service->createIndividual(
            $data,
            auth()->user(),
            GuarantorUploadData::fromIndividualRequest($request),
        );

        return $this->successResponse(
            GuarantorResource::make($guarantorRequest)
        );
    }

    /**
     * Create a company guarantor request with installments.
     *
     * @authenticated
     *
     * @bodyParam counterparty_phone string required Counterparty phone number.
     * @bodyParam project_type string required Project type. Example: Construction
     * @bodyParam total_amount number required Total contract amount.
     * @bodyParam installments array required Installment schedule.
     * @bodyParam company_name string required Company name.
     * @bodyParam signature file required Requester signature file.
     *
     * @response 200 {
     *   "status": true,
     *   "message": "Guarantor request created successfully",
     *   "data": {
     *     "id": "01234567-89ab-cdef-0123-456789abcdef",
     *     "type": { "value": "company", "label": "Company", "color": "#8b5cf6" },
     *     "status": { "value": "pending_admin", "label": "Pending Admin Review", "color": "#f59e0b" },
     *     "installments": [
     *       { "order": 1, "amount": 10000.00, "due_date": "2026-07-01", "status": { "value": "pending", "label": "Pending", "color": "#f59e0b" } }
     *     ],
     *     "company_detail": { "company_name": "Acme Corp" }
     *   }
     * }
     * @response 401 { "success": false, "message": "Unauthenticated." }
     * @response 422 { "success": false, "message": "The sum of installment amounts must equal the total contract amount" }
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
            GuarantorUploadData::fromCompanyRequest($request),
        );

        return $this->successResponse(
            GuarantorResource::make($guarantorRequest)
        );
    }

    /**
     * Show guarantor request details.
     *
     * @authenticated
     *
     * @urlParam guarantorRequest string required Guarantor request UUID.
     *
     * @response 200 {
     *   "status": true,
     *   "data": {
     *     "id": "01234567-89ab-cdef-0123-456789abcdef",
     *     "type": { "value": "individual", "label": "Individual", "color": "#3b82f6" },
     *     "status": { "value": "approved_by_admin", "label": "Approved by admin", "color": "#3b82f6" },
     *     "title": "Project guarantee",
     *     "installments": [],
     *     "status_histories": [],
     *     "media": []
     *   }
     * }
     * @response 401 { "success": false, "message": "Unauthenticated." }
     * @response 403 { "success": false, "message": "You are not authorized to perform this action" }
     * @response 404 { "success": false, "message": "Guarantor request not found" }
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
     * Update guarantor request.
     *
     * Only allowed when status is `new` and the authenticated user is the requester.
     *
     * @authenticated
     *
     * @urlParam guarantorRequest string required Guarantor request UUID.
     *
     * @response 200 { "status": true, "message": "Guarantor request updated successfully", "data": {} }
     * @response 401 { "success": false, "message": "Unauthenticated." }
     * @response 403 { "success": false, "message": "You are not authorized to perform this action" }
     * @response 422 { "success": false, "message": "You can only update requests with status New" }
     */
    public function update(
        UpdateGuarantorRequest $request,
        GuarantorRequest $guarantorRequest,
    ): JsonResponse {
        $this->authorize('update', $guarantorRequest);

        $updated = $this->service->update(
            $guarantorRequest,
            $request->validated(),
            GuarantorUploadData::fromUpdateRequest($request),
        );

        return $this->successResponse(
            GuarantorResource::make($this->service->loadForShow($updated))
        );
    }

    /**
     * Delete guarantor request.
     *
     * Only allowed when status is `new` and the authenticated user is the requester.
     *
     * @authenticated
     *
     * @urlParam guarantorRequest string required Guarantor request UUID.
     *
     * @response 200 { "status": true, "message": "Guarantor request deleted successfully" }
     * @response 401 { "success": false, "message": "Unauthenticated." }
     * @response 403 { "success": false, "message": "You are not authorized to perform this action" }
     * @response 422 { "success": false, "message": "You can only delete requests with status New" }
     */
    public function destroy(GuarantorRequest $guarantorRequest): JsonResponse
    {
        $this->authorize('delete', $guarantorRequest);
        $this->service->delete($guarantorRequest);

        return $this->successMessageResponse(__('guarantor.deleted_successfully'));
    }

    /**
     * Reject a guarantor request (counterparty only).
     *
     * Counterparty may reject from `approved_by_admin` with a mandatory reason (max 1000).
     * Terminal — no payment, chat, or further party transitions.
     *
     * @authenticated
     *
     * @urlParam guarantorRequest string required Guarantor request UUID.
     *
     * @bodyParam reason string required Why the request is being rejected (max 1000).
     *
     * @response 200 { "success": true, "data": { "status": { "value": "rejected" } }, "errors": {}, "message": "", "token": "" }
     * @response 401 { "success": false, "data": [], "errors": {}, "message": "Unauthenticated.", "token": "" }
     * @response 403 { "success": false, "data": [], "errors": {}, "message": "This action is unauthorized.", "token": "" }
     * @response 422 { "success": false, "data": [], "errors": { "reason": ["The reason field is required."] }, "message": "Validation Failed", "token": "" }
     */
    public function reject(
        RejectGuarantorRequest $request,
        GuarantorRequest $guarantorRequest,
    ): JsonResponse {
        $this->authorize('reject', $guarantorRequest);

        $actorRole = $this->service->resolveActorRole($guarantorRequest, auth()->user());

        $updated = $this->service->reject(
            $guarantorRequest,
            auth()->user(),
            $actorRole,
            (string) $request->validated('reason'),
        );

        return $this->successResponse(
            GuarantorResource::make($this->service->loadForShow($updated))
        );
    }

    /**
     * End a guarantor request (asymmetric by actor role).
     *
     * **Counterparty (client):** ends immediately → `ended`, wallet release unchanged.
     * **Requester (service provider):** transitions to `pending_counterparty_end_approval`
     * (no wallet change). Counterparty must then approve or reject via dedicated endpoints.
     *
     * Either party may call End from `in_progress` or `overdue`. Not available while
     * disputed, from `accepted`, or while an end request is already pending.
     *
     * @authenticated
     *
     * @urlParam guarantorRequest string required Guarantor request UUID.
     *
     * @response 200 { "success": true, "data": { "status": { "value": "ended" } }, "errors": {}, "message": "", "token": "" }
     * @response 200 { "success": true, "data": { "status": { "value": "pending_counterparty_end_approval" } }, "errors": {}, "message": "", "token": "" }
     * @response 401 { "success": false, "data": [], "errors": {}, "message": "Unauthenticated.", "token": "" }
     * @response 403 { "success": false, "data": [], "errors": {}, "message": "This action is unauthorized.", "token": "" }
     * @response 422 { "success": false, "data": [], "errors": {}, "message": "This status transition is not allowed", "token": "" }
     */
    public function end(GuarantorRequest $guarantorRequest): JsonResponse
    {
        $this->authorize('end', $guarantorRequest);

        $actorRole = $this->service->resolveActorRole($guarantorRequest, auth()->user());

        $updated = $this->service->end(
            $guarantorRequest,
            auth()->user(),
            $actorRole,
        );

        return $this->successResponse(
            GuarantorResource::make($this->service->loadForShow($updated))
        );
    }

    /**
     * Approve a pending end request (counterparty only).
     *
     * From `pending_counterparty_end_approval` → `ended`, with the same wallet release
     * mechanics as a counterparty-initiated End.
     *
     * @authenticated
     *
     * @urlParam guarantorRequest string required Guarantor request UUID.
     *
     * @response 200 { "success": true, "data": { "status": { "value": "ended" } }, "errors": {}, "message": "", "token": "" }
     * @response 401 { "success": false, "data": [], "errors": {}, "message": "Unauthenticated.", "token": "" }
     * @response 403 { "success": false, "data": [], "errors": {}, "message": "This action is unauthorized.", "token": "" }
     * @response 422 { "success": false, "data": [], "errors": {}, "message": "This status transition is not allowed", "token": "" }
     */
    public function approveEnd(GuarantorRequest $guarantorRequest): JsonResponse
    {
        $this->authorize('approveEnd', $guarantorRequest);

        $actorRole = $this->service->resolveActorRole($guarantorRequest, auth()->user());

        $updated = $this->service->approveEnd(
            $guarantorRequest,
            auth()->user(),
            $actorRole,
        );

        return $this->successResponse(
            GuarantorResource::make($this->service->loadForShow($updated))
        );
    }

    /**
     * Reject a pending end request (counterparty only).
     *
     * Requires a reason (max 1000). Reverts to whichever status the request came from
     * (`in_progress` or `overdue`), derived from status history. No wallet change.
     * Dispute is not available while pending — reject first, then dispute from the reverted status.
     *
     * @authenticated
     *
     * @urlParam guarantorRequest string required Guarantor request UUID.
     *
     * @bodyParam reason string required Why the end request is being rejected (max 1000).
     *
     * @response 200 { "success": true, "data": { "status": { "value": "in_progress" } }, "errors": {}, "message": "", "token": "" }
     * @response 401 { "success": false, "data": [], "errors": {}, "message": "Unauthenticated.", "token": "" }
     * @response 403 { "success": false, "data": [], "errors": {}, "message": "This action is unauthorized.", "token": "" }
     * @response 422 { "success": false, "data": [], "errors": { "reason": ["The reason field is required."] }, "message": "Validation Failed", "token": "" }
     */
    public function rejectEnd(
        RejectEndRequestRequest $request,
        GuarantorRequest $guarantorRequest,
    ): JsonResponse {
        $this->authorize('rejectEnd', $guarantorRequest);

        $actorRole = $this->service->resolveActorRole($guarantorRequest, auth()->user());

        $updated = $this->service->rejectEnd(
            $guarantorRequest,
            auth()->user(),
            $actorRole,
            (string) $request->validated('reason'),
        );

        return $this->successResponse(
            GuarantorResource::make($this->service->loadForShow($updated))
        );
    }

    /**
     * Open a dispute on a guarantor request.
     *
     * Either party may open a dispute from `in_progress` or `overdue` with a mandatory reason (max 1000).
     * Freezes End and further installment payments; chat and Admin Cancel remain available.
     *
     * @authenticated
     *
     * @urlParam guarantorRequest string required Guarantor request UUID.
     *
     * @bodyParam reason string required Why the dispute is being opened (max 1000).
     *
     * @response 200 { "success": true, "data": { "status": { "value": "disputed" } }, "errors": {}, "message": "", "token": "" }
     * @response 401 { "success": false, "data": [], "errors": {}, "message": "Unauthenticated.", "token": "" }
     * @response 403 { "success": false, "data": [], "errors": {}, "message": "This action is unauthorized.", "token": "" }
     * @response 422 { "success": false, "data": [], "errors": { "reason": ["The reason field is required."] }, "message": "Validation Failed", "token": "" }
     * @response 422 { "success": false, "data": [], "errors": {}, "message": "This status transition is not allowed", "token": "" }
     */
    public function dispute(
        OpenGuarantorDisputeRequest $request,
        GuarantorRequest $guarantorRequest,
    ): JsonResponse {
        $this->authorize('dispute', $guarantorRequest);

        $actorRole = $this->service->resolveActorRole($guarantorRequest, auth()->user());

        $updated = $this->service->openDispute(
            $guarantorRequest,
            auth()->user(),
            $actorRole,
            (string) $request->validated('reason'),
        );

        return $this->successResponse(
            GuarantorResource::make($this->service->loadForShow($updated))
        );
    }

    /**
     * Withdraw from a guarantor request before payment.
     *
     * Requester may withdraw from `approved_by_admin`. Either party may withdraw from `accepted`.
     * Optional reason (max 1000). No wallet impact — no payment has occurred at these stages.
     *
     * @authenticated
     *
     * @urlParam guarantorRequest string required Guarantor request UUID.
     *
     * @bodyParam reason string optional Why the party is withdrawing (max 1000).
     *
     * @response 200 { "success": true, "data": { "status": { "value": "withdrawn" } }, "errors": {}, "message": "", "token": "" }
     * @response 401 { "success": false, "data": [], "errors": {}, "message": "Unauthenticated.", "token": "" }
     * @response 403 { "success": false, "data": [], "errors": {}, "message": "This action is unauthorized.", "token": "" }
     * @response 422 { "success": false, "data": [], "errors": {}, "message": "This guarantor request cannot be withdrawn at its current stage", "token": "" }
     */
    public function withdraw(
        WithdrawGuarantorRequest $request,
        GuarantorRequest $guarantorRequest,
    ): JsonResponse {
        $this->authorize('withdraw', $guarantorRequest);

        $actorRole = $this->service->resolveActorRole($guarantorRequest, auth()->user());

        $updated = $this->service->withdraw(
            $guarantorRequest,
            auth()->user(),
            $actorRole,
            $request->validated('reason'),
        );

        return $this->successResponse(
            GuarantorResource::make($this->service->loadForShow($updated))
        );
    }

    /**
     * Accept a guarantor request (counterparty only).
     *
     * Counterparty must upload a digital signature. Status must be `approved_by_admin`.
     * Use this dedicated endpoint — there is no generic status-update route for parties.
     *
     * @authenticated
     *
     * @urlParam guarantorRequest string required Guarantor request UUID.
     *
     * @bodyParam signature file required Counterparty signature (jpg, jpeg, png, pdf; max 5MB).
     *
     * @response 200 { "success": true, "data": { "status": { "value": "accepted" } }, "errors": {}, "message": "", "token": "" }
     * @response 401 { "success": false, "data": [], "errors": {}, "message": "Unauthenticated.", "token": "" }
     * @response 403 { "success": false, "data": [], "errors": {}, "message": "This action is unauthorized.", "token": "" }
     * @response 422 { "success": false, "data": [], "errors": { "signature": ["The signature field is required."] }, "message": "Validation Failed", "token": "" }
     */
    public function accept(
        AcceptGuarantorRequest $request,
        GuarantorRequest $guarantorRequest,
    ): JsonResponse {
        $this->authorize('accept', $guarantorRequest);

        $updated = $this->service->accept(
            $guarantorRequest,
            auth()->user(),
            GuarantorAcceptUploadData::fromRequest($request),
        );

        return $this->successResponse(
            GuarantorResource::make($this->service->loadForShow($updated))
        );
    }

    /**
     * Pay individual guarantor request.
     *
     * Initiates payment for an individual guarantor request. Counterparty only, status must be `accepted`.
     *
     * @authenticated
     *
     * @urlParam guarantorRequest string required Guarantor request UUID.
     *
     * @response 200 {
     *   "status": true,
     *   "data": { "payment_url": "https://payment-gateway.example/pay/..." }
     * }
     * @response 401 { "success": false, "message": "Unauthenticated." }
     * @response 403 { "success": false, "message": "You are not authorized to perform this action" }
     * @response 422 { "success": false, "message": "This status transition is not allowed" }
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
     * Delete media from guarantor request.
     *
     * Only allowed when status is `new` and the authenticated user is the requester.
     *
     * @authenticated
     *
     * @urlParam guarantorRequest string required Guarantor request UUID.
     * @urlParam media string required Media UUID.
     *
     * @response 200 { "status": true, "message": "Media deleted successfully" }
     * @response 401 { "success": false, "message": "Unauthenticated." }
     * @response 403 { "success": false, "message": "You are not authorized to perform this action" }
     * @response 422 { "success": false, "message": "You can only delete media when status is New" }
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
