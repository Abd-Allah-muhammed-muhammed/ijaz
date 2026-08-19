<?php

namespace Modules\Payout\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Payout\Actions\Payout\ConfirmPayoutTransferAction;
use Modules\Payout\Actions\Payout\CreatePayoutRequestAction;
use Modules\Payout\Actions\Payout\FailPayoutTransferAction;
use Modules\Payout\Actions\Payout\ListPendingPayoutRequestsForDashboardAction;
use Modules\Payout\DTOs\CreatePayoutRequestData;
use Modules\Payout\Models\PayoutRequest;

class PayoutService
{
    public function __construct(
        private readonly CreatePayoutRequestAction $createAction,
        private readonly ConfirmPayoutTransferAction $confirmAction,
        private readonly FailPayoutTransferAction $failAction,
        private readonly ListPendingPayoutRequestsForDashboardAction $listPendingForDashboardAction,
    ) {}

    public function createForOperation(
        Model $operation,
        Model $recipient,
        float $amount,
        ?int $makerAdminId = null,
    ): PayoutRequest {
        return $this->createAction->handle(new CreatePayoutRequestData(
            operation: $operation,
            recipient: $recipient,
            amount: $amount,
            makerAdminId: $makerAdminId,
        ));
    }

    public function listPendingForDashboard(Request $request): LengthAwarePaginator
    {
        return $this->listPendingForDashboardAction->handle($request);
    }

    public function confirmTransfer(
        PayoutRequest $payoutRequest,
        int $adminId,
        string $gatewayReference,
    ): PayoutRequest {
        return $this->confirmAction->handle($payoutRequest, $adminId, $gatewayReference);
    }

    public function failTransfer(PayoutRequest $payoutRequest, string $failureReason): PayoutRequest
    {
        return $this->failAction->handle($payoutRequest, $failureReason);
    }
}
