<?php

namespace Modules\Wallet\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
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
     * Caller must wrap in DB::transaction().
     *
     * @return array{topUpRequest: TopUpRequest, paymentResult: PaymentInitResult|null}
     */
    public function create(Model $owner, CreateTopUpData $data): array
    {
        return $this->createAction->handle($owner, $data);
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

    public function listForOwner(Model $owner, int $perPage = 16): LengthAwarePaginator
    {
        return $this->listForOwnerAction->handle($owner, $perPage);
    }

    public function listAll(int $perPage = 16): LengthAwarePaginator
    {
        return $this->listAllAction->handle($perPage);
    }
}
