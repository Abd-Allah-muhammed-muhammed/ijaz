<?php

namespace Modules\Catalog\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Modules\Catalog\Http\Requests\Dashboard\PropertyTypeRequest;
use Modules\Catalog\Http\Resources\Dashboard\PropertyTypeCollection;
use Modules\Catalog\Http\Resources\Dashboard\PropertyTypeResource;
use Modules\Catalog\Models\PropertyType;
use Throwable;

class PropertyTypeController extends Controller implements HasMiddleware
{
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
        $propertyTypes = PropertyType::with(['translation'])
            ->when($request->input('search'), function ($query, $v) {
                return $query->whereTranslationLike('name', "%{$v}%");
            })
            ->paginate($request->integer('per_page', 10))
            ->withQueryString();

        return inertia('Dashboard/PropertyTypes/Index', [
            'prams' => fn () => $request->all() ?: [],
            'rows' => fn () => PropertyTypeCollection::make($propertyTypes),
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
        DB::beginTransaction();
        try {
            $data = $request->validated();
            PropertyType::create($data);
            DB::commit();

            return redirect()->route('dashboard.property-types.index')->with('success', __('data saved successfully'));
        } catch (Throwable $throwable) {
            DB::rollBack();
            report($throwable);

            return redirect()->back()->with('error', __('something went wrong'));
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PropertyType $propertyType)
    {
        $propertyType->load(['translations']);

        return inertia('Dashboard/PropertyTypes/Edit', [
            'propertyType' => PropertyTypeResource::make($propertyType),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PropertyTypeRequest $request, PropertyType $propertyType)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            $propertyType->update($data);
            DB::commit();

            return redirect()->route('dashboard.property-types.index')->with('success', __('data updated successfully'));
        } catch (Throwable $throwable) {
            DB::rollBack();
            report($throwable);

            return redirect()->back()->with('error', __('something went wrong'));
        }
    }

    public function updateStatus(Request $request, PropertyType $propertyType)
    {
        DB::beginTransaction();
        try {
            $propertyType->update([
                'is_active' => $request->boolean('is_active'),
            ]);
            DB::commit();

            return redirect()->route('dashboard.property-types.index')->with('success', __('data updated successfully'));
        } catch (Throwable $throwable) {
            DB::rollBack();
            report($throwable);

            return redirect()->back()->with('error', __('something went wrong'));
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PropertyType $propertyType)
    {
        $propertyType->delete();

        return redirect()->route('dashboard.property-types.index')->with('success', __('data deleted successfully'));
    }
}
