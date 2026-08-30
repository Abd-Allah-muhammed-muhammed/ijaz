<?php

namespace Modules\Opportunity\Services;

use App\Models\Admin;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Modules\Opportunity\Actions\Dashboard\AdminApproveOpportunityAction;
use Modules\Opportunity\Actions\Dashboard\AdminRejectOpportunityAction;
use Modules\Opportunity\Actions\Opportunity\CreateOpportunityAction;
use Modules\Opportunity\Actions\Opportunity\DeleteOpportunityAction;
use Modules\Opportunity\Actions\Opportunity\DeleteOpportunityForDashboardAction;
use Modules\Opportunity\Actions\Opportunity\EnsureOpportunityVisibleToViewerAction;
use Modules\Opportunity\Actions\Opportunity\GetOpportunityDashboardStatsAction;
use Modules\Opportunity\Actions\Opportunity\ListOpportunitiesForDashboardAction;
use Modules\Opportunity\Actions\Opportunity\RenewOpportunityAction;
use Modules\Opportunity\Actions\Opportunity\ResubmitOpportunityAction;
use Modules\Opportunity\Actions\Opportunity\UpdateOpportunityAction;
use Modules\Opportunity\Contracts\Repositories\OpportunityRepositoryInterface;
use Modules\Opportunity\DTOs\OpportunityData;
use Modules\Opportunity\Http\Requests\Dashboard\ApproveOpportunityRequest;
use Modules\Opportunity\Http\Requests\Dashboard\RejectOpportunityRequest;
use Modules\Opportunity\Models\Opportunity;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Throwable;

class OpportunityService
{
    public function __construct(
        private readonly OpportunityRepositoryInterface $opportunities,
        private readonly CreateOpportunityAction $createAction,
        private readonly UpdateOpportunityAction $updateAction,
        private readonly DeleteOpportunityAction $deleteAction,
        private readonly DeleteOpportunityForDashboardAction $deleteForDashboardAction,
        private readonly RenewOpportunityAction $renewAction,
        private readonly ListOpportunitiesForDashboardAction $listForDashboardAction,
        private readonly GetOpportunityDashboardStatsAction $getDashboardStatsAction,
        private readonly EnsureOpportunityVisibleToViewerAction $ensureVisibleToViewerAction,
        private readonly AdminApproveOpportunityAction $approveAction,
        private readonly AdminRejectOpportunityAction $rejectAction,
        private readonly ResubmitOpportunityAction $resubmitAction,
    ) {}

    public function listForDashboard(Request $request): LengthAwarePaginator
    {
        return $this->listForDashboardAction->handle($request);
    }

    /**
     * @return array{total: int, pending_admin: int, active: int, ended: int, cancelled: int}
     */
    public function getDashboardStats(): array
    {
        return $this->getDashboardStatsAction->handle();
    }

    public function listPublic(?Model $actor = null, int $perPage = 10, ?int $regionId = null, ?int $cityId = null): LengthAwarePaginator
    {
        return $this->opportunities->listPublic($actor, $perPage, $regionId, $cityId);
    }

    /**
     * @param  array<int, string>|null  $statuses
     */
    public function listByActor(Model $actor, int $perPage = 10, ?array $statuses = null): LengthAwarePaginator
    {
        return $this->opportunities->listByActor($actor, $perPage, $statuses);
    }

    public function loadForShow(Opportunity $opportunity, ?Model $actor = null): Opportunity
    {
        $this->ensureVisibleToViewerAction->handle($opportunity, $actor);

        return $this->opportunities->loadForShow($opportunity, $actor);
    }

    /**
     * @throws Throwable
     */
    public function create(OpportunityData $data, Model $author, Request $request): Opportunity
    {
        return $this->createAction->handle($data, $author, $request);
    }

    /**
     * @throws Throwable
     */
    public function update(Opportunity $opportunity, OpportunityData $data, Request $request): Opportunity
    {
        return $this->updateAction->handle($opportunity, $data, $request);
    }

    /**
     * @throws Throwable
     */
    public function delete(Opportunity $opportunity): void
    {
        $this->deleteAction->handle($opportunity);
    }

    /**
     * Admin dashboard soft-delete — no status restriction.
     * Distinct from delete() (API New-only path).
     */
    public function deleteForDashboard(Opportunity $opportunity): void
    {
        $this->deleteForDashboardAction->handle($opportunity);
    }

    /**
     * @throws Throwable
     */
    public function approve(
        Opportunity $opportunity,
        ApproveOpportunityRequest $formRequest,
        Admin $admin,
    ): Opportunity {
        return $this->approveAction->handle(
            $opportunity,
            $formRequest->validated('notes'),
            $admin,
        );
    }

    /**
     * @throws Throwable
     */
    public function reject(
        Opportunity $opportunity,
        RejectOpportunityRequest $formRequest,
        Admin $admin,
    ): void {
        $this->rejectAction->handle(
            $opportunity,
            $formRequest->validated('reason'),
            $formRequest->validated('notes'),
            $admin,
        );
    }

    /**
     * @throws Throwable
     */
    public function renew(Opportunity $opportunity, ?Carbon $expiresAt = null): Opportunity
    {
        return $this->renewAction->handle($opportunity, $expiresAt);
    }

    /**
     * @throws Throwable
     */
    public function resubmit(Opportunity $opportunity): Opportunity
    {
        return $this->resubmitAction->handle($opportunity);
    }

    public function deleteMedia(Opportunity $opportunity, Media $media): void
    {
        $media->delete();
    }
}
