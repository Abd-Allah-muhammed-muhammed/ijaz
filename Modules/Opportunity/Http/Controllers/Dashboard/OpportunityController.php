<?php

namespace Modules\Opportunity\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Response;
use Modules\Opportunity\Enums\OpportunityStatusEnum;
use Modules\Opportunity\Http\Resources\Dashboard\OpportunityDashboardCollection;
use Modules\Opportunity\Http\Resources\Dashboard\OpportunityDashboardResource;
use Modules\Opportunity\Models\Opportunity;

class OpportunityController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:show opportunities', only: ['index', 'show']),
            new Middleware('permission:delete opportunities', only: ['destroy']),
        ];
    }

    public function index(Request $request): Response
    {
        return inertia('Dashboard/Opportunity/Index', [
            'rows' => fn () => OpportunityDashboardCollection::make(
                Opportunity::query()
                    ->with(['author', 'region.translation', 'city.translation'])
                    ->withCount(['offers', 'comments'])
                    ->when($request->search, fn ($query) => $query->where('title', 'like', "%{$request->search}%"))
                    ->when($request->status, fn ($query) => $query->where('status', $request->status))
                    ->latest()
                    ->paginate($request->integer('per_page', 10))
                    ->withQueryString()
            ),
            'prams' => fn () => $request->all() ?: [],
            'selects' => fn () => [
                'statuses' => OpportunityStatusEnum::collect()
                    ->map(fn ($status) => $status->toArray())
                    ->values(),
            ],
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

    public function destroy(Opportunity $opportunity): RedirectResponse
    {
        $opportunity->delete();

        return back()->with('success', __('opportunity.deleted_successfully'));
    }
}
