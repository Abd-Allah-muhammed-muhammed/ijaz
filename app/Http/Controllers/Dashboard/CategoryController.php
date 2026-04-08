<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\CategoryFeesTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\CategoryRequest;
use App\Http\Resources\Dashboard\CategoryCollection;
use App\Http\Resources\Dashboard\CategoryResource;
use App\Http\Resources\General\ReactSelectResource;
use App\Models\Category;
use App\Services\Normalize\Normalize;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Throwable;

class CategoryController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:show categories', only: ['index', 'show']),
            new Middleware('permission:create categories', only: ['create', 'store']),
            new Middleware('permission:edit categories', only: ['edit', 'update']),
            new Middleware('permission:delete categories', only: ['destroy']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $categories = Category::withCount(['children'])
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

        return inertia('Dashboard/Categories/Index', [
            'prams' => fn () => $request->all() ?: [],
            'rows' => fn () => CategoryCollection::make($categories),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Category $category)
    {
        $category->load(['translations', 'parent']);

        return inertia('Dashboard/Categories/Edit', [
            'category' => CategoryResource::make($category),
            'categories' => CategoryResource::collection(
                Category::with(['translation'])
                    ->where('id', '!=', $category->id)
                    ->get()
            ),
            'fees_types' => ReactSelectResource::collection(CategoryFeesTypeEnum::collect()),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CategoryRequest $request, Category $category)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            if (isset($data['fees_type']) && CategoryFeesTypeEnum::tryFrom($data['fees_type']) === CategoryFeesTypeEnum::INHERITED) {
                $data['fees'] = null;
            }
            if ($request->hasFile('icon')) {
                $category->deleteIcon();
                $data['icon'] = $request->file('icon')->store('categories');
            }
            $category->update($data);
            DB::commit();

            return redirect()->route('dashboard.categories.index')->with('success', __('data updated successfully'));
        } catch (Throwable $throwable) {
            DB::rollBack();
            report($throwable);

            return redirect()->back()->with('error', __('something went wrong'));
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CategoryRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            if (isset($data['fees_type']) && $data['fees_type'] === CategoryFeesTypeEnum::INHERITED) {
                unset($data['fees']);
            }
            $data['icon'] = $request->file('icon')->store('categories');
            Category::create($data);
            DB::commit();

            return redirect()->route('dashboard.categories.index')->with('success', __('data saved successfully'));
        } catch (Throwable $throwable) {
            DB::rollBack();
            report($throwable);

            return redirect()->back()->with('error', __('something went wrong'));
        }

    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return inertia('Dashboard/Categories/Create', [
            'categories' => CategoryResource::collection(Category::with(['translation'])->get()),
            'fees_types' => ReactSelectResource::collection(CategoryFeesTypeEnum::collect()),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category)
    {
        if ($category->children()->exists()) {
            return redirect()->back()->with('error', __('this category has subcategories'));
        }
        $category->delete();
        $category->deleteIcon();

        return redirect()->route('dashboard.categories.index')->with('success', __('data deleted successfully'));
    }
}
