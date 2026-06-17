<?php

namespace Modules\Guarantor\Services;

use App\Models\Admin;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Modules\Guarantor\Actions\Dashboard\AdminApproveGuarantorAction;
use Modules\Guarantor\Actions\Dashboard\AdminCancelGuarantorAction;
use Modules\Guarantor\Actions\Dashboard\AdminRejectGuarantorAction;
use Modules\Guarantor\Actions\Installment\ReleaseInstallmentAction;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Http\Requests\Dashboard\ApproveGuarantorRequest;
use Modules\Guarantor\Http\Requests\Dashboard\CancelGuarantorRequest;
use Modules\Guarantor\Http\Requests\Dashboard\RejectGuarantorRequest;
use Modules\Guarantor\Models\GuarantorInstallment;
use Modules\Guarantor\Models\GuarantorRequest;

class GuarantorDashboardService
{
    public function __construct(
        private readonly AdminApproveGuarantorAction $approveAction,
        private readonly AdminRejectGuarantorAction $rejectAction,
        private readonly AdminCancelGuarantorAction $cancelAction,
        private readonly ReleaseInstallmentAction $releaseAction,
    ) {}

    public function listAll(Request $request, int $perPage = 15): LengthAwarePaginator
    {
        return GuarantorRequest::query()
            ->with(['requester', 'counterparty', 'media'])
            ->withCount(['installments'])
            ->when($request->search, fn ($q) => $q->where('title', 'like', "%{$request->search}%"))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->type, fn ($q) => $q->where('type', $request->type))
            ->when($request->date_from, fn ($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->date_to, fn ($q) => $q->whereDate('created_at', '<=', $request->date_to))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @return array<string, int>
     */
    public function getStats(): array
    {
        return [
            'total' => GuarantorRequest::count(),
            'pending_admin' => GuarantorRequest::where('status', GuarantorStatusEnum::PendingAdmin)->count(),
            'in_progress' => GuarantorRequest::where('status', GuarantorStatusEnum::InProgress)->count(),
            'overdue' => GuarantorRequest::where('status', GuarantorStatusEnum::Overdue)->count(),
            'ended' => GuarantorRequest::where('status', GuarantorStatusEnum::Ended)->count(),
        ];
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
        $request->delete();
    }
}
