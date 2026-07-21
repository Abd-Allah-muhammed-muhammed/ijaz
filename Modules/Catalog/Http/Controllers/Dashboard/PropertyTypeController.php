<?php

namespace Modules\Catalog\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Modules\Catalog\Contracts\Services\PropertyTypeServiceInterface;
use Modules\Catalog\DTOs\StorePropertyTypeDTO;
use Modules\Catalog\DTOs\UpdatePropertyTypeDTO;
use Modules\Catalog\Http\Requests\Dashboard\PropertyTypeRequest;
use Modules\Catalog\Http\Resources\Dashboard\PropertyTypeCollection;
use Modules\Catalog\Http\Resources\Dashboard\PropertyTypeResource;
use Modules\Catalog\Models\PropertyType;
use Throwable;

class PropertyTypeController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly PropertyTypeServiceInterface $service,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:show propertyTypes', only: ['index', 'show']),
            new Middleware('permission:create propertyTypes', only: ['create', 'store']),
            new Middleware('permission:edit propertyTypes', only: ['edit', 'update']),
            new Middleware('permission:delete propertyTypes', only: ['destroy']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return inertia('Dashboard/PropertyTypes/Index', [
            'prams' => fn () => $request->all() ?: [],
            'rows' => fn () => PropertyTypeCollection::make($this->service->index($request)),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return inertia('Dashboard/PropertyTypes/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PropertyTypeRequest $request): ?RedirectResponse
    {
        try {
            $this->service->store(StorePropertyTypeDTO::fromValidated($request->validated()));

            return redirect()->route('dashboard.property-types.index')->with('success', __('data saved successfully'));
        } catch (Throwable $throwable) {
            report($throwable);

            return redirect()->back()->with('error', __('something went wrong'));
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PropertyType $propertyType)
    {
        return inertia('Dashboard/PropertyTypes/Edit', [
            'propertyType' => PropertyTypeResource::make($this->service->show($propertyType)),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PropertyTypeRequest $request, PropertyType $propertyType)
    {
        try {
            $this->service->update($propertyType, UpdatePropertyTypeDTO::fromValidated($request->validated()));

            return redirect()->route('dashboard.property-types.index')->with('success', __('data updated successfully'));
        } catch (Throwable $throwable) {
            report($throwable);

            return redirect()->back()->with('error', __('something went wrong'));
        }
    }

    public function updateStatus(Request $request, PropertyType $propertyType)
    {
        try {
            $this->service->updateStatus($propertyType, $request->boolean('is_active'));

            return redirect()->route('dashboard.property-types.index')->with('success', __('data updated successfully'));
        } catch (Throwable $throwable) {
            report($throwable);

            return redirect()->back()->with('error', __('something went wrong'));
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PropertyType $propertyType)
    {
        $this->service->destroy($propertyType);

        return redirect()->route('dashboard.property-types.index')->with('success', __('data deleted successfully'));
    }
}
