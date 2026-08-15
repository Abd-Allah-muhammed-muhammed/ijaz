<?php

namespace Modules\Guarantor\Services;

use App\Models\Admin;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Modules\Guarantor\Actions\Dashboard\AdminApproveGuarantorAction;
use Modules\Guarantor\Actions\Dashboard\AdminRejectGuarantorAction;
use Modules\Guarantor\Actions\Dashboard\DeleteGuarantorForDashboardAction;
use Modules\Guarantor\Actions\Guarantor\CancelGuarantorAction;
use Modules\Guarantor\Actions\Installment\ReleaseInstallmentAction;
use Modules\Guarantor\Contracts\Repositories\GuarantorRepositoryInterface;
use Modules\Guarantor\Http\Requests\Dashboard\ApproveGuarantorRequest;
use Modules\Guarantor\Http\Requests\Dashboard\CancelGuarantorRequest;
use Modules\Guarantor\Http\Requests\Dashboard\RejectGuarantorRequest;
use Modules\Guarantor\Models\GuarantorInstallment;
use Modules\Guarantor\Models\GuarantorRequest;

class GuarantorDashboardService
{
    public function __construct(
        private readonly GuarantorRepositoryInterface $repository,
        private readonly AdminApproveGuarantorAction $approveAction,
        private readonly AdminRejectGuarantorAction $rejectAction,
        private readonly CancelGuarantorAction $cancelAction,
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

    public function releaseInstallment(GuarantorInstallment $installment): void
    {
        $this->releaseAction->handle($installment, 'admin');
    }

    public function delete(GuarantorRequest $request): void
    {
        $this->deleteForDashboardAction->handle($request);
    }
}
