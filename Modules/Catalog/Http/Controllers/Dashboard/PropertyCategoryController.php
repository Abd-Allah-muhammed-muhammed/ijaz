<?php

namespace Modules\Catalog\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Modules\Catalog\Contracts\Services\PropertyCategoryServiceInterface;
use Modules\Catalog\DTOs\StorePropertyCategoryDTO;
use Modules\Catalog\DTOs\UpdatePropertyCategoryDTO;
use Modules\Catalog\Exceptions\CatalogException;
use Modules\Catalog\Http\Requests\Dashboard\PropertyCategoryRequest;
use Modules\Catalog\Http\Resources\Dashboard\PropertyCategoryCollection;
use Modules\Catalog\Http\Resources\Dashboard\PropertyCategoryResource;
use Modules\Catalog\Models\PropertyCategory;
use Throwable;

class PropertyCategoryController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly PropertyCategoryServiceInterface $service,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:show propertyCategories', only: ['index', 'show']),
            new Middleware('permission:create propertyCategories', only: ['create', 'store']),
            new Middleware('permission:edit propertyCategories', only: ['edit', 'update']),
            new Middleware('permission:delete propertyCategories', only: ['destroy']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return inertia('Dashboard/PropertyCategories/Index', [
            'prams' => fn () => $request->all() ?: [],
            'rows' => fn () => PropertyCategoryCollection::make($this->service->index($request)),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return inertia('Dashboard/PropertyCategories/Create', [
            'categories' => PropertyCategoryResource::collection(
                $this->service->getRootCategories()
            ),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PropertyCategoryRequest $request)
    {

        try {
            $this->service->store(StorePropertyCategoryDTO::fromValidated($request->validated()));

            return redirect()->route('dashboard.property-categories.index')->with('success', __('data saved successfully'));
        } catch (Throwable $throwable) {
            report($throwable);

            return redirect()->back()->with('error', __('something went wrong'));
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PropertyCategory $propertyCategory)
    {
        return inertia('Dashboard/PropertyCategories/Edit', [
            'category' => PropertyCategoryResource::make($this->service->show($propertyCategory)),
            'categories' => PropertyCategoryResource::collection(
                $this->service->getRootCategories(excludeId: $propertyCategory->id)
            ),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PropertyCategoryRequest $request, PropertyCategory $propertyCategory)
    {
        try {
            $this->service->update($propertyCategory, UpdatePropertyCategoryDTO::fromValidated($request->validated()));

            return redirect()->route('dashboard.property-categories.index')->with('success', __('data updated successfully'));
        } catch (Throwable $throwable) {
            report($throwable);

            return redirect()->back()->with('error', __('something went wrong'));
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PropertyCategory $propertyCategory): RedirectResponse
    {
        try {
            $this->service->destroy($propertyCategory);

            return redirect()->route('dashboard.property-categories.index')->with('success', __('data deleted successfully'));
        } catch (CatalogException $exception) {
            return redirect()->back()->with('error', $exception->getMessage());
        } catch (Throwable $throwable) {
            report($throwable);

            return redirect()->back()->with('error', __('something went wrong'));
        }
    }
}
