<?php

namespace App\Http\Controllers\Dashboard;

use App\Contracts\Services\CarTypeServiceInterface;
use App\DTOs\CarType\StoreCarTypeDTO;
use App\DTOs\CarType\UpdateCarTypeDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\CarTypeRequest;
use App\Http\Resources\Dashboard\CarTypeCollection;
use App\Http\Resources\Dashboard\CarTypeResource;
use App\Models\CarType;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class CarTypeController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly CarTypeServiceInterface $service,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:show carTypes', only: ['index', 'show']),
            new Middleware('permission:create carTypes', only: ['create', 'store']),
            new Middleware('permission:edit carTypes', only: ['edit', 'update']),
            new Middleware('permission:delete carTypes', only: ['destroy']),
        ];
    }

    public function index(Request $request)
    {
        $carTypes = $this->service->index($request);

        return inertia('Dashboard/CarTypes/Index', [
            'prams' => fn () => $request->all() ?: [],
            'rows' => fn () => CarTypeCollection::make($carTypes),
        ]);
    }

    public function create()
    {
        return inertia('Dashboard/CarTypes/Create');
    }

    public function store(CarTypeRequest $request)
    {
        $dto = StoreCarTypeDTO::fromRequest($request);
        $this->service->store($dto);

        return redirect()->route('dashboard.car-types.index')->with('success', __('data saved successfully'));
    }

    public function edit(CarType $carType)
    {
        $carType = $this->service->show($carType);

        return inertia('Dashboard/CarTypes/Edit', [
            'carType' => CarTypeResource::make($carType),
        ]);
    }

    public function update(CarTypeRequest $request, CarType $carType)
    {
        $dto = UpdateCarTypeDTO::fromRequest($request);
        $this->service->update($carType, $dto);

        return redirect()->route('dashboard.car-types.index')->with('success', __('data updated successfully'));
    }

    public function updateStatus(Request $request, CarType $carType)
    {
        $this->service->updateStatus($carType, $request->boolean('is_active'));

        return redirect()->route('dashboard.car-types.index')->with('success', __('data updated successfully'));
    }

    public function destroy(CarType $carType)
    {
        $this->service->destroy($carType);

        return redirect()->route('dashboard.car-types.index')->with('success', __('data deleted successfully'));
    }
}
