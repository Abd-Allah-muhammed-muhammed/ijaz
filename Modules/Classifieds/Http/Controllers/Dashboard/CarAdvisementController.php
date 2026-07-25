<?php

namespace Modules\Classifieds\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Inertia\Response;
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
        return [];
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
        $carAdvisement->load(['carBrand', 'carType', 'carCategory', 'city', 'region', 'user', 'media']);

        return inertia('Dashboard/CarAdvisement/Show', [
            'row' => CarAdvisementResource::make($carAdvisement),
        ]);
    }

    public function update(Request $request, CarAdvisement $carAdvisement): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string'],
        ]);

        $carAdvisement->update(['status' => $validated['status']]);

        return redirect()->back()->with('success', __('advisement.status_updated_successfully'));
    }
}
