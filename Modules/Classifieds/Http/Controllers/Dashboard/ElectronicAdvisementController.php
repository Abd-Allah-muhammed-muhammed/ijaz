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
use Modules\Classifieds\Http\Resources\Dashboard\ElectronicAdvisementCollection;
use Modules\Classifieds\Http\Resources\Dashboard\ElectronicAdvisementResource;
use Modules\Classifieds\Models\ElectronicAdvisement;
use Modules\Classifieds\Services\ElectronicAdvisementService;

class ElectronicAdvisementController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly ElectronicAdvisementService $service,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:show electronicAdvisements', only: ['index', 'show']),
            new Middleware('permission:edit electronicAdvisements', only: ['update']),
            new Middleware('permission:delete electronicAdvisements', only: ['destroy']),
        ];
    }

    public function index(Request $request): Response
    {
        return inertia('Dashboard/ElectronicAdvisement/Index', [
            'rows' => fn () => ElectronicAdvisementCollection::make(
                $this->service->listForDashboard($request)
            ),
            'prams' => $request->all() ?: [],
            'selects' => fn () => $this->service->resolveDashboardSelects($request),
        ]);
    }

    public function show(ElectronicAdvisement $electronicAdvisement): Response
    {
        $electronicAdvisement->load(['deviceCategory', 'electronicBrand', 'city', 'region', 'user', 'media']);

        return inertia('Dashboard/ElectronicAdvisement/Show', [
            'row' => ElectronicAdvisementResource::make($electronicAdvisement),
        ]);
    }

    public function update(Request $request, ElectronicAdvisement $electronicAdvisement): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', Rule::enum(AdvisementStatusEnum::class)],
        ]);

        $this->service->updateStatusForDashboard(
            $electronicAdvisement,
            AdvisementStatusEnum::from($validated['status']),
        );

        return redirect()->back()->with('success', __('advisement.status_updated_successfully'));
    }

    public function destroy(ElectronicAdvisement $electronicAdvisement): RedirectResponse
    {
        $this->service->deleteForDashboard($electronicAdvisement);

        return redirect()
            ->route('dashboard.electronic-advisements.index')
            ->with('success', __('data deleted successfully'));
    }
}
