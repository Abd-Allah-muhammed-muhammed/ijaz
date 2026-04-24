<?php

namespace App\Http\Controllers\Dashboard;

use App\Contracts\Services\CarCategoryServiceInterface;
use App\DTOs\CarCategory\StoreCarCategoryDTO;
use App\DTOs\CarCategory\UpdateCarCategoryDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\CarCategoryRequest;
use App\Http\Resources\Dashboard\CarCategoryCollection;
use App\Http\Resources\Dashboard\CarCategoryResource;
use App\Models\CarCategory;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

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
            'categories' => CarCategoryResource::collection(CarCategory::with(['translation'])->whereNull('parent_id')->get()),
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
            'categories' => CarCategoryResource::collection(
                CarCategory::with(['translation'])
                    ->whereNull('parent_id')
                    ->get()
            ),
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
