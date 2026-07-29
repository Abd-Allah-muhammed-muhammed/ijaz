<?php

namespace Modules\Classifieds\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rule;
use Inertia\Response;
use Modules\Classifieds\Enums\AdvisementStatusEnum;
use Modules\Classifieds\Http\Resources\Dashboard\PropertyAdvisementCollection;
use Modules\Classifieds\Http\Resources\Dashboard\PropertyAdvisementResource;
use Modules\Classifieds\Models\PropertyAdvisement;
use Modules\Classifieds\Services\PropertyAdvisementService;

class PropertyAdvisementController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly PropertyAdvisementService $service,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:show propertyAdvisements', only: ['index', 'show']),
            new Middleware('permission:edit propertyAdvisements', only: ['update']),
            new Middleware('permission:delete propertyAdvisements', only: ['destroy']),
        ];
    }

    public function index(Request $request): Response
    {
        return inertia('Dashboard/PropertyAdvisement/Index', [
            'rows' => fn () => PropertyAdvisementCollection::make(
                $this->service->listForDashboard($request)
            ),
            'prams' => $request->all() ?: [],
            'selects' => fn () => $this->service->resolveDashboardSelects($request),
        ]);
    }

    public function show(PropertyAdvisement $propertyAdvisement): Response
    {
        $propertyAdvisement->load(['propertyType', 'city', 'region', 'category', 'user', 'media']);

        return inertia('Dashboard/PropertyAdvisement/Show', [
            'row' => PropertyAdvisementResource::make($propertyAdvisement),
        ]);
    }

    public function update(Request $request, PropertyAdvisement $propertyAdvisement): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', Rule::enum(AdvisementStatusEnum::class)],
        ]);

        $this->service->updateStatusForDashboard(
            $propertyAdvisement,
            AdvisementStatusEnum::from($validated['status']),
        );

        return redirect()->back()->with('success', __('advisement.status_updated_successfully'));
    }

    public function destroy(PropertyAdvisement $propertyAdvisement): RedirectResponse
    {
        $this->service->deleteForDashboard($propertyAdvisement);

        return redirect()
            ->route('dashboard.property-advisements.index')
            ->with('success', __('data deleted successfully'));
    }
}
