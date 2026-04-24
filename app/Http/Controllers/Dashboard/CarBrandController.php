<?php

namespace App\Http\Controllers\Dashboard;

use App\Contracts\Services\CarBrandServiceInterface;
use App\DTOs\CarBrand\StoreCarBrandDTO;
use App\DTOs\CarBrand\UpdateCarBrandDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\CarBrandRequest;
use App\Http\Resources\Dashboard\CarBrandCollection;
use App\Http\Resources\Dashboard\CarBrandResource;
use App\Models\CarBrand;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class CarBrandController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly CarBrandServiceInterface $service,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:show carBrands', only: ['index', 'show']),
            new Middleware('permission:create carBrands', only: ['create', 'store']),
            new Middleware('permission:edit carBrands', only: ['edit', 'update']),
            new Middleware('permission:delete carBrands', only: ['destroy']),
        ];
    }

    public function index(Request $request)
    {
        $carBrands = $this->service->index($request);

        return inertia('Dashboard/CarBrands/Index', [
            'prams' => fn () => $request->all() ?: [],
            'rows' => fn () => CarBrandCollection::make($carBrands),
        ]);
    }

    public function create()
    {
        return inertia('Dashboard/CarBrands/Create');
    }

    public function store(CarBrandRequest $request)
    {
        $dto = StoreCarBrandDTO::fromRequest($request);
        $this->service->store($dto);

        return redirect()->route('dashboard.car-brands.index')->with('success', __('data saved successfully'));
    }

    public function edit(CarBrand $carBrand)
    {
        $carBrand = $this->service->show($carBrand);

        return inertia('Dashboard/CarBrands/Edit', [
            'carBrand' => CarBrandResource::make($carBrand),
        ]);
    }

    public function update(CarBrandRequest $request, CarBrand $carBrand)
    {
        $dto = UpdateCarBrandDTO::fromRequest($request);
        $this->service->update($carBrand, $dto);

        return redirect()->route('dashboard.car-brands.index')->with('success', __('data updated successfully'));
    }

    public function updateStatus(Request $request, CarBrand $carBrand)
    {
        $this->service->updateStatus($carBrand, $request->boolean('is_active'));

        return redirect()->route('dashboard.car-brands.index')->with('success', __('data updated successfully'));
    }

    public function destroy(CarBrand $carBrand)
    {
        $this->service->destroy($carBrand);

        return redirect()->route('dashboard.car-brands.index')->with('success', __('data deleted successfully'));
    }
}
