<?php

namespace Modules\Geo\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Response;
use Modules\Geo\DTOs\StoreRegionDTO;
use Modules\Geo\DTOs\UpdateRegionDTO;
use Modules\Geo\Http\Requests\Dashboard\RegionRequest;
use Modules\Geo\Http\Resources\Dashboard\RegionCollection;
use Modules\Geo\Http\Resources\Dashboard\RegionResource;
use Modules\Geo\Models\Region;
use Modules\Geo\Services\RegionService;
use Throwable;

class RegionController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly RegionService $service,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:show regions', only: ['index', 'show']),
            new Middleware('permission:create regions', only: ['create', 'store']),
            new Middleware('permission:edit regions', only: ['edit', 'update']),
            new Middleware('permission:delete regions', only: ['destroy']),
        ];
    }

    public function index(Request $request): Response
    {
        $rows = $this->service->index($request);

        return inertia('Dashboard/Regions/Index', [
            'prams' => $request->all() ?: [],
            'rows' => RegionCollection::make($rows),
        ]);
    }

    public function create(): Response
    {
        return inertia('Dashboard/Regions/Create', []);
    }

    /**
     * @throws Throwable
     */
    public function store(RegionRequest $request): RedirectResponse
    {
        try {
            $this->service->store(StoreRegionDTO::fromValidated($request->validated()));

            return redirect()->route('dashboard.regions.index')->with('success', __('data saved successfully'));
        } catch (Throwable $th) {
            report($th);

            return redirect()->back()->with('error', __('something went wrong'));
        }
    }

    public function edit(Region $region): Response
    {
        $region = $this->service->show($region);

        return inertia('Dashboard/Regions/Edit', [
            'row' => RegionResource::make($region),
        ]);
    }

    /**
     * @throws Throwable
     */
    public function update(RegionRequest $request, Region $region): RedirectResponse
    {
        try {
            $this->service->update($region, UpdateRegionDTO::fromValidated($request->validated()));

            return redirect()->route('dashboard.regions.index')->with('success', __('data saved successfully'));
        } catch (Throwable $th) {
            report($th);

            return redirect()->back()->with('error', __('something went wrong'));
        }
    }

    public function destroy(Region $region): RedirectResponse
    {
        $this->service->destroy($region);

        return redirect()->route('dashboard.regions.index')->with('success', __('data deleted successfully'));
    }
}
