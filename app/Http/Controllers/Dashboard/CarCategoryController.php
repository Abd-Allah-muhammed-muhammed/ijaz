<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\CarCategoryRequest;
use App\Http\Resources\Dashboard\CarCategoryCollection;
use App\Http\Resources\Dashboard\CarCategoryResource;
use App\Models\CarCategory;
use App\Services\Normalize\Normalize;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Throwable;

class CarCategoryController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:show carCategories', only: ['index', 'show']),
            new Middleware('permission:create carCategories', only: ['create', 'store']),
            new Middleware('permission:edit carCategories', only: ['edit', 'update']),
            new Middleware('permission:delete carCategories', only: ['destroy']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $categories = CarCategory::withCount(['children'])
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

        return inertia('Dashboard/CarCategories/Index', [
            'prams' => fn () => $request->all() ?: [],
            'rows' => fn () => CarCategoryCollection::make($categories),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return inertia('Dashboard/CarCategories/Create', [
            'categories' => CarCategoryResource::collection(CarCategory::with(['translation'])->whereNull('parent_id')->get()),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CarCategoryRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            $data['icon'] = $request->file('icon')?->store('car_categories');
            CarCategory::create($data);
            DB::commit();

            return redirect()->route('dashboard.car-categories.index')->with('success', __('data saved successfully'));
        } catch (Throwable $throwable) {
            DB::rollBack();
            report($throwable);

            return redirect()->back()->with('error', __('something went wrong'));
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CarCategory $car_category)
    {
        $car_category->load(['translations', 'parent']);

        return inertia('Dashboard/CarCategories/Edit', [
            'category' => CarCategoryResource::make($car_category),
            'categories' => CarCategoryResource::collection(
                CarCategory::with(['translation'])
                    ->whereNull('parent_id')
                    ->get()
            ),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CarCategoryRequest $request, CarCategory $car_category)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            if ($request->hasFile('icon')) {
                $car_category->deleteIcon();
                $data['icon'] = $request->file('icon')->store('car_categories');
            }
            $car_category->update($data);
            DB::commit();

            return redirect()->route('dashboard.car-categories.index')->with('success', __('data updated successfully'));
        } catch (Throwable $throwable) {
            DB::rollBack();
            report($throwable);

            return redirect()->back()->with('error', __('something went wrong'));
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CarCategory $car_category)
    {
        if ($car_category->children()->exists()) {
            return redirect()->back()->with('error', __('this category has subcategories'));
        }
        $car_category->delete();
        $car_category->deleteIcon();

        return redirect()->route('dashboard.car-categories.index')->with('success', __('data deleted successfully'));
    }
}
