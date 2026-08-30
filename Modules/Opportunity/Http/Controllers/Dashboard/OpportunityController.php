<?php

namespace Modules\Opportunity\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Response;
use Modules\Opportunity\Enums\OpportunityStatusEnum;
use Modules\Opportunity\Http\Requests\Dashboard\ApproveOpportunityRequest;
use Modules\Opportunity\Http\Requests\Dashboard\RejectOpportunityRequest;
use Modules\Opportunity\Http\Resources\Dashboard\OpportunityDashboardCollection;
use Modules\Opportunity\Http\Resources\Dashboard\OpportunityDashboardResource;
use Modules\Opportunity\Models\Opportunity;
use Modules\Opportunity\Services\OpportunityService;

class OpportunityController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly OpportunityService $service,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:show opportunities', only: ['index', 'show']),
            new Middleware('permission:manage opportunities', only: ['approveByAdmin', 'rejectByAdmin']),
            new Middleware('permission:delete opportunities', only: ['destroy']),
        ];
    }

    public function index(Request $request): Response
    {
        return inertia('Dashboard/Opportunity/Index', [
            'rows' => fn () => OpportunityDashboardCollection::make(
                $this->service->listForDashboard($request)
            ),
            'prams' => fn () => $request->all() ?: [],
            'selects' => fn () => [
                'statuses' => OpportunityStatusEnum::collect()
                    ->map(fn ($status) => $status->toArray())
                    ->values(),
            ],
            'stats' => fn () => $this->service->getDashboardStats(),
        ]);
    }

    public function show(Opportunity $opportunity): Response
    {
        $opportunity->load([
            'author',
            'region.translation',
            'city.translation',
            'media',
            'acceptedOffer.author',
            'offers.author',
            'comments.author',
        ]);
        $opportunity->loadCount(['offers', 'comments']);

        return inertia('Dashboard/Opportunity/Show', [
            'opportunity' => fn () => new OpportunityDashboardResource($opportunity),
        ]);
    }

    public function approveByAdmin(
        ApproveOpportunityRequest $request,
        Opportunity $opportunity,
    ): RedirectResponse {
        $this->service->approve($opportunity, $request, auth('admin')->user());

        return back()->with('success', __('opportunity.status_updated_successfully'));
    }

    public function rejectByAdmin(
        RejectOpportunityRequest $request,
        Opportunity $opportunity,
    ): RedirectResponse {
        $this->service->reject($opportunity, $request, auth('admin')->user());

        return back()->with('success', __('opportunity.status_updated_successfully'));
    }

    public function destroy(Opportunity $opportunity): RedirectResponse
    {
        $this->service->deleteForDashboard($opportunity);

        return back()->with('success', __('opportunity.deleted_successfully'));
    }
}
