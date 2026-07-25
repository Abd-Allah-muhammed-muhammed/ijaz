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
use Modules\Classifieds\Http\Resources\Dashboard\InstituteAdvisementCollection;
use Modules\Classifieds\Http\Resources\Dashboard\InstituteAdvisementResource;
use Modules\Classifieds\Models\InstituteAdvisement;
use Modules\Classifieds\Services\InstituteAdvisementService;

class InstituteAdvisementController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly InstituteAdvisementService $service,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:show instituteAdvisements', only: ['index', 'show']),
            new Middleware('permission:edit instituteAdvisements', only: ['update']),
            new Middleware('permission:delete instituteAdvisements', only: ['destroy']),
        ];
    }

    public function index(Request $request): Response
    {
        return inertia('Dashboard/InstituteAdvisement/Index', [
            'rows' => fn () => InstituteAdvisementCollection::make(
                $this->service->listForDashboard($request)
            ),
            'prams' => $request->all() ?: [],
            'selects' => fn () => $this->service->resolveDashboardSelects($request),
        ]);
    }

    public function show(InstituteAdvisement $instituteAdvisement): Response
    {
        $instituteAdvisement->load(['specialization', 'city', 'region', 'user', 'media']);

        return inertia('Dashboard/InstituteAdvisement/Show', [
            'row' => InstituteAdvisementResource::make($instituteAdvisement),
        ]);
    }

    public function update(Request $request, InstituteAdvisement $instituteAdvisement): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', Rule::enum(AdvisementStatusEnum::class)],
        ]);

        $instituteAdvisement->update(['status' => $validated['status']]);

        return redirect()->back()->with('success', __('advisement.status_updated_successfully'));
    }

    public function destroy(InstituteAdvisement $instituteAdvisement): RedirectResponse
    {
        $instituteAdvisement->delete();

        return redirect()
            ->route('dashboard.institute-advisements.index')
            ->with('success', __('data deleted successfully'));
    }
}
