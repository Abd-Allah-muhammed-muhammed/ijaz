<?php

namespace Modules\Guarantor\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use MMAE\ApiResponse\Traits\HasApiResponse;
use Modules\Guarantor\Http\Requests\PayInstallmentRequest;
use Modules\Guarantor\Http\Resources\Api\InstallmentResource;
use Modules\Guarantor\Models\GuarantorInstallment;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Guarantor\Services\GuarantorInstallmentService;

#[Group('Guarantor Installments')]
class InstallmentController extends Controller
{
    use HasApiResponse;

    public function __construct(
        private readonly GuarantorInstallmentService $service,
    ) {}

    /**
     * List installments for a guarantor request.
     *
     * @authenticated
     *
     * @urlParam guarantorRequest string required Guarantor request UUID.
     *
     * @response 200 {
     *   "status": true,
     *   "data": [
     *     {
     *       "id": "01234567-89ab-cdef-0123-456789abcdef",
     *       "order": 1,
     *       "amount": 10000.00,
     *       "due_date": "2026-07-01",
     *       "status": { "value": "pending", "label": "Pending", "color": "#f59e0b" },
     *       "paid_at": null,
     *       "released_at": null,
     *       "is_past_due": false
     *     }
     *   ]
     * }
     * @response 401 { "success": false, "message": "Unauthenticated." }
     * @response 403 { "success": false, "message": "You are not authorized to perform this action" }
     * @response 404 { "success": false, "message": "Guarantor request not found" }
     */
    public function index(GuarantorRequest $guarantorRequest): JsonResponse
    {
        $this->authorize('view', $guarantorRequest);

        return $this->successResponse(
            InstallmentResource::collection(
                $guarantorRequest->installments()->orderBy('order')->get()
            )
        );
    }

    /**
     * Pay an installment.
     *
     * Initiates payment for a company guarantor installment. Counterparty only.
     * Previous installments must be paid first.
     *
     * @authenticated
     *
     * @urlParam guarantorRequest string required Guarantor request UUID.
     * @urlParam installment string required Installment UUID.
     *
     * @response 200 {
     *   "status": true,
     *   "message": "Payment initiated successfully",
     *   "data": { "payment_url": "https://payment-gateway.example/pay/..." }
     * }
     * @response 401 { "success": false, "message": "Unauthenticated." }
     * @response 403 { "success": false, "message": "You are not authorized to perform this action" }
     * @response 422 { "success": false, "message": "Previous installment must be paid first" }
     */
    public function pay(
        PayInstallmentRequest $request,
        GuarantorRequest $guarantorRequest,
        GuarantorInstallment $installment,
    ): JsonResponse {
        $this->authorize('pay', $installment);

        $paymentResponse = $this->service->pay(
            $guarantorRequest,
            $installment,
            auth()->user()
        );

        return $this->successResponse($paymentResponse);
    }
}
