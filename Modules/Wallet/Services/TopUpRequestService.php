<?php

namespace Modules\Wallet\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Payment\DTOs\PaymentInitResult;
use Modules\Wallet\Actions\TopUp\CancelTopUpRequestAction;
use Modules\Wallet\Actions\TopUp\CreateTopUpRequestAction;
use Modules\Wallet\Actions\TopUp\ListAllTopUpRequestsAction;
use Modules\Wallet\Actions\TopUp\ListTopUpRequestsForOwnerAction;
use Modules\Wallet\Actions\TopUp\UpdateTopUpStatusForDashboardAction;
use Modules\Wallet\DTOs\CreateTopUpData;
use Modules\Wallet\Models\TopUpRequest;

class TopUpRequestService
{
    public function __construct(
        private readonly CreateTopUpRequestAction $createAction,
        private readonly CancelTopUpRequestAction $cancelAction,
        private readonly UpdateTopUpStatusForDashboardAction $updateStatusForDashboardAction,
        private readonly ListTopUpRequestsForOwnerAction $listForOwnerAction,
        private readonly ListAllTopUpRequestsAction $listAllAction,
    ) {}

    /**
     * Create a top-up request (online or offline).
     *
     * @return array{topUpRequest: TopUpRequest, paymentResult: PaymentInitResult|null}
     */
    public function create(Model $owner, CreateTopUpData $data): array
    {
        return DB::transaction(fn (): array => $this->createAction->handle($owner, $data));
    }

    public function cancel(TopUpRequest $topUpRequest): void
    {
        $this->cancelAction->handle($topUpRequest);
    }

    /**
     * Admin dashboard approve/reject. Credits wallet only for Approved + Offline
     * (online credit is handled by HandleTopUpPaymentCompleted).
     */
    public function updateStatusForDashboard(
        TopUpRequest $topUpRequest,
        string $status,
        ?string $adminNotes,
        int $adminId,
    ): TopUpRequest {
        return $this->updateStatusForDashboardAction->handle(
            $topUpRequest,
            $status,
            $adminNotes,
            $adminId,
        );
    }

    public function listForOwner(Model $owner, Request $request): LengthAwarePaginator
    {
        return $this->listForOwnerAction->handle($owner, $request);
    }

    public function listAll(Request $request): LengthAwarePaginator
    {
        return $this->listAllAction->handle($request);
    }
}
