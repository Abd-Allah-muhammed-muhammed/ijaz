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
use Modules\Classifieds\Http\Resources\Dashboard\CarAdvisementCollection;
use Modules\Classifieds\Http\Resources\Dashboard\CarAdvisementResource;
use Modules\Classifieds\Models\CarAdvisement;
use Modules\Classifieds\Services\CarAdvisementService;

class CarAdvisementController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly CarAdvisementService $service,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:show carAdvisements', only: ['index', 'show']),
            new Middleware('permission:edit carAdvisements', only: ['update']),
            new Middleware('permission:delete carAdvisements', only: ['destroy']),
        ];
    }

    public function index(Request $request): Response
    {
        return inertia('Dashboard/CarAdvisement/Index', [
            'rows' => fn () => CarAdvisementCollection::make(
                $this->service->listForDashboard($request)
            ),
            'prams' => $request->all() ?: [],
            'selects' => fn () => $this->service->resolveDashboardSelects($request),
        ]);
    }

    public function show(CarAdvisement $carAdvisement): Response
    {
        $carAdvisement->load([
            'carBrand.translations',
            'carType.translations',
            'carCategory.translations',
            'city.translations',
            'region.translations',
            'bank.translations',
            'bank.media',
            'user',
            'media',
        ]);

        return inertia('Dashboard/CarAdvisement/Show', [
            'row' => CarAdvisementResource::make($carAdvisement),
        ]);
    }

    public function update(Request $request, CarAdvisement $carAdvisement): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', Rule::enum(AdvisementStatusEnum::class)],
        ]);

        $this->service->updateStatusForDashboard(
            $carAdvisement,
            AdvisementStatusEnum::from($validated['status']),
        );

        return redirect()->back()->with('success', __('advisement.status_updated_successfully'));
    }

    public function destroy(CarAdvisement $carAdvisement): RedirectResponse
    {
        $this->service->deleteForDashboard($carAdvisement);

        return redirect()
            ->route('dashboard.car-advisements.index')
            ->with('success', __('data deleted successfully'));
    }
}
