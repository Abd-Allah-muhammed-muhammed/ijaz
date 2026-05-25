<?php

namespace Modules\Catalog\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\Normalize\Normalize;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Modules\Catalog\Http\Requests\Dashboard\DeviceCategoryRequest;
use Modules\Catalog\Http\Resources\Dashboard\DeviceCategoryCollection;
use Modules\Catalog\Http\Resources\Dashboard\DeviceCategoryResource;
use Modules\Catalog\Models\DeviceCategory;
use Throwable;

class DeviceCategoryController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:show deviceCategories', only: ['index', 'show']),
            new Middleware('permission:create deviceCategories', only: ['create', 'store']),
            new Middleware('permission:edit deviceCategories', only: ['edit', 'update']),
            new Middleware('permission:delete deviceCategories', only: ['destroy']),
        ];
    }

    public function index(Request $request)
    {
        $categories = DeviceCategory::withCount(['children'])
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

        return inertia('Dashboard/DeviceCategories/Index', [
            'prams' => fn () => $request->all() ?: [],
            'rows' => fn () => DeviceCategoryCollection::make($categories),
        ]);
    }

    public function create()
    {
        return inertia('Dashboard/DeviceCategories/Create', [
            'categories' => DeviceCategoryResource::collection(
                DeviceCategory::with(['translation'])->whereNull('parent_id')->get()),
        ]);
    }

    public function store(DeviceCategoryRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            $data['icon'] = $request->file('icon')?->store('device_categories');
            DeviceCategory::create($data);
            DB::commit();

            return redirect()->route('dashboard.device-categories.index')->with('success', __('data saved successfully'));
        } catch (Throwable $throwable) {
            DB::rollBack();
            report($throwable);

            return redirect()->back()->with('error', __('something went wrong'));
        }
    }

    public function edit(DeviceCategory $device_category)
    {
        $device_category->load(['translations', 'parent']);

        return inertia('Dashboard/DeviceCategories/Edit', [
            'category' => DeviceCategoryResource::make($device_category),
            'categories' => DeviceCategoryResource::collection(
                DeviceCategory::with(['translation'])
                    ->whereNull('parent_id')
                    ->get()
            ),
        ]);
    }

    public function update(DeviceCategoryRequest $request, DeviceCategory $device_category)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            if ($request->hasFile('icon')) {
                $device_category->deleteIcon();
                $data['icon'] = $request->file('icon')->store('device_categories');
            }
            $device_category->update($data);
            DB::commit();

            return redirect()->route('dashboard.device-categories.index')->with('success', __('data updated successfully'));
        } catch (Throwable $throwable) {
            DB::rollBack();
            report($throwable);

            return redirect()->back()->with('error', __('something went wrong'));
        }
    }

    public function destroy(DeviceCategory $device_category)
    {
        if ($device_category->children()->exists()) {
            return redirect()->back()->with('error', __('this category has subcategories'));
        }
        $device_category->delete();
        $device_category->deleteIcon();

        return redirect()->route('dashboard.device-categories.index')->with('success', __('data deleted successfully'));
    }
}
