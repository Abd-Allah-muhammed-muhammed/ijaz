<?php

namespace Modules\Catalog\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Response;
use Modules\Catalog\Contracts\Services\DeviceCategoryServiceInterface;
use Modules\Catalog\DTOs\StoreDeviceCategoryDTO;
use Modules\Catalog\DTOs\UpdateDeviceCategoryDTO;
use Modules\Catalog\Http\Requests\Dashboard\DeviceCategoryRequest;
use Modules\Catalog\Http\Resources\Dashboard\DeviceCategoryCollection;
use Modules\Catalog\Http\Resources\Dashboard\DeviceCategoryResource;
use Modules\Catalog\Models\DeviceCategory;

class DeviceCategoryController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly DeviceCategoryServiceInterface $service,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:show deviceCategories', only: ['index', 'show']),
            new Middleware('permission:create deviceCategories', only: ['create', 'store']),
            new Middleware('permission:edit deviceCategories', only: ['edit', 'update']),
            new Middleware('permission:delete deviceCategories', only: ['destroy']),
        ];
    }

    public function index(Request $request): Response
    {
        $categories = $this->service->index($request);

        return inertia('Dashboard/DeviceCategories/Index', [
            'prams' => fn () => $request->all() ?: [],
            'rows' => fn () => DeviceCategoryCollection::make($categories),
        ]);
    }

    public function create(): Response
    {
        return inertia('Dashboard/DeviceCategories/Create', [
            'categories' => DeviceCategoryResource::collection(
                DeviceCategory::with(['translation'])->whereNull('parent_id')->get()
            ),
        ]);
    }

    public function store(DeviceCategoryRequest $request): RedirectResponse
    {
        $dto = StoreDeviceCategoryDTO::fromRequest($request);
        $this->service->store($dto);

        return redirect()->route('dashboard.device-categories.index')->with('success', __('data saved successfully'));
    }

    public function edit(DeviceCategory $device_category): Response
    {
        $device_category = $this->service->show($device_category);

        return inertia('Dashboard/DeviceCategories/Edit', [
            'category' => DeviceCategoryResource::make($device_category),
            'categories' => DeviceCategoryResource::collection(
                DeviceCategory::with(['translation'])
                    ->whereNull('parent_id')
                    ->get()
            ),
        ]);
    }

    public function update(DeviceCategoryRequest $request, DeviceCategory $device_category): RedirectResponse
    {
        $dto = UpdateDeviceCategoryDTO::fromRequest($request, $device_category);
        $this->service->update($device_category, $dto);

        return redirect()->route('dashboard.device-categories.index')->with('success', __('data updated successfully'));
    }

    public function destroy(DeviceCategory $device_category): RedirectResponse
    {
        $this->service->destroy($device_category);

        return redirect()->route('dashboard.device-categories.index')->with('success', __('data deleted successfully'));
    }
}
