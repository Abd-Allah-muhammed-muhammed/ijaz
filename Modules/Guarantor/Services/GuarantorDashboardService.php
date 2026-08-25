<?php

namespace Modules\Guarantor\Services;

use App\Models\Admin;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Modules\Guarantor\Actions\Dashboard\AdminApproveGuarantorAction;
use Modules\Guarantor\Actions\Dashboard\AdminRejectGuarantorAction;
use Modules\Guarantor\Actions\Dashboard\DeleteGuarantorForDashboardAction;
use Modules\Guarantor\Actions\Guarantor\CancelGuarantorAction;
use Modules\Guarantor\Actions\Guarantor\ResolveDisputeEscalateAction;
use Modules\Guarantor\Actions\Guarantor\ResolveDisputeFullToPartyAction;
use Modules\Guarantor\Actions\Guarantor\ResolveDisputePercentageSplitAction;
use Modules\Guarantor\Actions\Installment\ReleaseInstallmentAction;
use Modules\Guarantor\Contracts\Repositories\GuarantorRepositoryInterface;
use Modules\Guarantor\Enums\GuarantorDisputeResolutionEnum;
use Modules\Guarantor\Http\Requests\Dashboard\ApproveGuarantorRequest;
use Modules\Guarantor\Http\Requests\Dashboard\CancelGuarantorRequest;
use Modules\Guarantor\Http\Requests\Dashboard\RejectGuarantorRequest;
use Modules\Guarantor\Http\Requests\Dashboard\ResolveGuarantorDisputeRequest;
use Modules\Guarantor\Models\GuarantorInstallment;
use Modules\Guarantor\Models\GuarantorRequest;

class GuarantorDashboardService
{
    public function __construct(
        private readonly GuarantorRepositoryInterface $repository,
        private readonly AdminApproveGuarantorAction $approveAction,
        private readonly AdminRejectGuarantorAction $rejectAction,
        private readonly CancelGuarantorAction $cancelAction,
        private readonly ResolveDisputeFullToPartyAction $resolveDisputeFullToPartyAction,
        private readonly ResolveDisputeEscalateAction $resolveDisputeEscalateAction,
        private readonly ResolveDisputePercentageSplitAction $resolveDisputePercentageSplitAction,
        private readonly ReleaseInstallmentAction $releaseAction,
        private readonly DeleteGuarantorForDashboardAction $deleteForDashboardAction,
    ) {}

    public function listAll(Request $request, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginateForDashboard($request, $perPage);
    }

    /**
     * @return array{total: int, pending_admin: int, in_progress: int, overdue: int, ended: int}
     */
    public function getStats(): array
    {
        return $this->repository->getDashboardStats();
    }

    public function approve(
        GuarantorRequest $request,
        ApproveGuarantorRequest $formRequest,
        Admin $admin,
    ): GuarantorRequest {
        return $this->approveAction->handle(
            $request,
            $formRequest->validated('notes'),
            $admin
        );
    }

    public function reject(
        GuarantorRequest $request,
        RejectGuarantorRequest $formRequest,
        Admin $admin,
    ): void {
        $this->rejectAction->handle(
            $request,
            $formRequest->validated('reason'),
            $formRequest->validated('notes'),
            $admin
        );
    }

    public function cancel(
        GuarantorRequest $request,
        CancelGuarantorRequest $formRequest,
        Admin $admin,
    ): void {
        $this->cancelAction->handle(
            $request,
            $formRequest->validated('reason'),
            $formRequest->validated('notes'),
            $admin
        );
    }

    public function resolveDispute(
        GuarantorRequest $request,
        ResolveGuarantorDisputeRequest $formRequest,
        Admin $admin,
    ): GuarantorRequest {
        $resolution = GuarantorDisputeResolutionEnum::from($formRequest->validated('resolution'));
        $notes = $formRequest->validated('notes');

        return match ($resolution) {
            GuarantorDisputeResolutionEnum::FullRequester => $this->resolveDisputeFullToPartyAction->handle(
                $request,
                $admin,
                'requester',
                $notes,
            ),
            GuarantorDisputeResolutionEnum::FullCounterparty => $this->resolveDisputeFullToPartyAction->handle(
                $request,
                $admin,
                'counterparty',
                $notes,
            ),
            GuarantorDisputeResolutionEnum::Escalate => $this->resolveDisputeEscalateAction->handle(
                $request,
                $admin,
                $notes,
            ),
            GuarantorDisputeResolutionEnum::PercentageSplit => $this->resolveDisputePercentageSplitAction->handle(
                $request,
                $admin,
                (int) $formRequest->validated('requester_percentage'),
                $notes,
            ),
        };
    }

    public function releaseInstallment(GuarantorInstallment $installment): void
    {
        $this->releaseAction->handle($installment, 'admin');
    }

    public function delete(GuarantorRequest $request): void
    {
        $this->deleteForDashboardAction->handle($request);
    }
}
