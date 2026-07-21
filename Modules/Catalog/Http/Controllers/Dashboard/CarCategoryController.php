<?php

namespace Modules\Catalog\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Modules\Catalog\Contracts\Services\CarCategoryServiceInterface;
use Modules\Catalog\DTOs\StoreCarCategoryDTO;
use Modules\Catalog\DTOs\UpdateCarCategoryDTO;
use Modules\Catalog\Http\Requests\Dashboard\CarCategoryRequest;
use Modules\Catalog\Http\Resources\Dashboard\CarCategoryCollection;
use Modules\Catalog\Http\Resources\Dashboard\CarCategoryResource;
use Modules\Catalog\Models\CarCategory;

class CarCategoryController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly CarCategoryServiceInterface $service,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:show carCategories', only: ['index', 'show']),
            new Middleware('permission:create carCategories', only: ['create', 'store']),
            new Middleware('permission:edit carCategories', only: ['edit', 'update']),
            new Middleware('permission:delete carCategories', only: ['destroy']),
        ];
    }

    public function index(Request $request)
    {
        $categories = $this->service->index($request);

        return inertia('Dashboard/CarCategories/Index', [
            'prams' => fn () => $request->all() ?: [],
            'rows' => fn () => CarCategoryCollection::make($categories),
        ]);
    }

    public function create()
    {
        return inertia('Dashboard/CarCategories/Create', [
            'categories' => CarCategoryResource::collection($this->service->getRootCategories()),
        ]);
    }

    public function store(CarCategoryRequest $request)
    {
        $dto = StoreCarCategoryDTO::fromRequest($request);
        $this->service->store($dto);

        return redirect()->route('dashboard.car-categories.index')->with('success', __('data saved successfully'));
    }

    public function edit(CarCategory $car_category)
    {
        $car_category = $this->service->show($car_category);

        return inertia('Dashboard/CarCategories/Edit', [
            'category' => CarCategoryResource::make($car_category),
            'categories' => CarCategoryResource::collection($this->service->getRootCategories()),
        ]);
    }

    public function update(CarCategoryRequest $request, CarCategory $car_category)
    {
        $dto = UpdateCarCategoryDTO::fromRequest($request);
        $this->service->update($car_category, $dto);

        return redirect()->route('dashboard.car-categories.index')->with('success', __('data updated successfully'));
    }

    public function destroy(CarCategory $car_category)
    {
        $this->service->destroy($car_category);

        return redirect()->route('dashboard.car-categories.index')->with('success', __('data deleted successfully'));
    }
}
