<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\PropertyCategoryRequest;
use App\Http\Resources\Dashboard\PropertyCategoryCollection;
use App\Http\Resources\Dashboard\PropertyCategoryResource;
use App\Models\PropertiyCategory;
use App\Services\Normalize\Normalize;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Throwable;

class PropertyCategoryController extends Controller implements HasMiddleware
{
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
        $categories = PropertiyCategory::withCount(['children'])
            ->with(['translation'])
            ->when($request->input('search'), function ($query, $v) {
                $v = Normalize::make($v, app()->getLocale());

                return $query->whereTranslationLike('normalized_title', "%{$v}%");
            })
            ->when(
                $request->integer('parent_id'),
                fn ($query, $v) => $query->where('parent_id', $v),
                fn ($query) => $query->whereNull('parent_id'),
            )
            ->paginate($request->integer('per_page', 10))
            ->withQueryString();

        return inertia('Dashboard/PropertyCategories/Index', [
            'prams' => fn () => $request->all() ?: [],
            'rows' => fn () => PropertyCategoryCollection::make($categories),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return inertia('Dashboard/PropertyCategories/Create', [
            'categories' => PropertyCategoryResource::collection(
                PropertiyCategory::with(['translation'])
                    ->whereNull('parent_id')
                    ->get()
            ),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PropertyCategoryRequest $request)
    {

        DB::beginTransaction();
        try {
            $data = $request->validated();
            PropertiyCategory::create($data);
            DB::commit();

            return redirect()->route('dashboard.property-categories.index')->with('success', __('data saved successfully'));
        } catch (Throwable $throwable) {
            DB::rollBack();
            report($throwable);

            return redirect()->back()->with('error', __('something went wrong'));
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PropertiyCategory $propertyCategory)
    {
        $propertyCategory->load(['translations', 'parent']);

        return inertia('Dashboard/PropertyCategories/Edit', [
            'category' => PropertyCategoryResource::make($propertyCategory),
            'categories' => PropertyCategoryResource::collection(
                PropertiyCategory::with(['translation'])
                    ->whereNull('parent_id')
                    ->get()
            ),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PropertyCategoryRequest $request, PropertiyCategory $propertyCategory)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            $propertyCategory->update($data);
            DB::commit();

            return redirect()->route('dashboard.property-categories.index')->with('success', __('data updated successfully'));
        } catch (Throwable $throwable) {
            DB::rollBack();
            report($throwable);

            return redirect()->back()->with('error', __('something went wrong'));
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PropertiyCategory $propertyCategory)
    {
        if ($propertyCategory->children()->exists()) {
            return redirect()->back()->with('error', __('this category has subcategories'));
        }
        $propertyCategory->delete();

        return redirect()->route('dashboard.property-categories.index')->with('success', __('data deleted successfully'));
    }
}
