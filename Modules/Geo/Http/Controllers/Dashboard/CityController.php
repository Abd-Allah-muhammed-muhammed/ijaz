<?php

namespace Modules\Geo\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Response;
use Modules\Geo\DTOs\StoreCityDTO;
use Modules\Geo\DTOs\UpdateCityDTO;
use Modules\Geo\Http\Requests\Dashboard\CityRequest;
use Modules\Geo\Http\Resources\Dashboard\CityCollection;
use Modules\Geo\Http\Resources\Dashboard\CityResource;
use Modules\Geo\Http\Resources\Dashboard\RegionResource;
use Modules\Geo\Models\City;
use Modules\Geo\Services\CityService;
use Throwable;

class CityController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly CityService $service,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:show cities', only: ['index', 'show']),
            new Middleware('permission:create cities', only: ['create', 'store']),
            new Middleware('permission:edit cities', only: ['edit', 'update']),
            new Middleware('permission:delete cities', only: ['destroy']),
        ];
    }

    public function index(Request $request): Response
    {
        $rows = $this->service->index($request);

        return inertia('Dashboard/Cities/Index', [
            'prams' => $request->all() ?: [],
            'rows' => CityCollection::make($rows),
        ]);
    }

    public function create(): Response
    {
        return inertia('Dashboard/Cities/Create', [
            'regions' => RegionResource::collection($this->service->getRegionsForDropdown()),
        ]);
    }

    /**
     * @throws Throwable
     */
    public function store(CityRequest $request): RedirectResponse
    {
        try {
            $this->service->store(StoreCityDTO::fromValidated($request->validated()));

            return redirect()->route('dashboard.cities.index')->with('success', __('data saved successfully'));
        } catch (Throwable $th) {
            report($th);

            return redirect()->back()->with('error', __('something went wrong'));
        }
    }

    public function edit(City $city): Response
    {
        $city = $this->service->show($city);

        return inertia('Dashboard/Cities/Edit', [
            'row' => CityResource::make($city),
            'regions' => RegionResource::collection($this->service->getRegionsForDropdown()),
        ]);
    }

    /**
     * @throws Throwable
     */
    public function update(CityRequest $request, City $city): RedirectResponse
    {
        try {
            $this->service->update($city, UpdateCityDTO::fromValidated($request->validated()));

            return redirect()->route('dashboard.cities.index')->with('success', __('data saved successfully'));
        } catch (Throwable $th) {
            report($th);

            return redirect()->back()->with('error', __('something went wrong'));
        }
    }

    /**
     * @throws Throwable
     */
    public function destroy(City $city): RedirectResponse
    {
        try {
            $this->service->destroy($city);

            return redirect()->route('dashboard.cities.index')->with('success', __('data deleted successfully'));
        } catch (Throwable $th) {
            report($th);

            return redirect()->back()->with('error', __('something went wrong'));
        }
    }
}
