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
     * Pay an installment (counterparty only).
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

        return $this->successResponse(
            $paymentResponse,
            __('guarantor.payment_initiated')
        );
    }
}
