<?php

namespace Modules\Marketplace\Http\Controllers\Dashboard;

use App\Enums\CategoryFeesTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\General\ReactSelectResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Response;
use Modules\Marketplace\DTOs\StoreCategoryDTO;
use Modules\Marketplace\DTOs\UpdateCategoryDTO;
use Modules\Marketplace\Exceptions\MarketplaceException;
use Modules\Marketplace\Http\Requests\Dashboard\CategoryRequest;
use Modules\Marketplace\Http\Resources\Dashboard\CategoryCollection;
use Modules\Marketplace\Http\Resources\Dashboard\CategoryResource;
use Modules\Marketplace\Models\Category;
use Modules\Marketplace\Services\CategoryService;
use Throwable;

class CategoryController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly CategoryService $service,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:show categories', only: ['index', 'show']),
            new Middleware('permission:create categories', only: ['create', 'store']),
            new Middleware('permission:edit categories', only: ['edit', 'update']),
            new Middleware('permission:delete categories', only: ['destroy']),
        ];
    }

    public function index(Request $request): Response
    {
        return inertia('Dashboard/Categories/Index', [
            'prams' => fn () => $request->all() ?: [],
            'rows' => fn () => CategoryCollection::make($this->service->index($request)),
        ]);
    }

    public function create(): Response
    {
        return inertia('Dashboard/Categories/Create', [
            'categories' => CategoryResource::collection($this->service->getAllForDropdown()),
            'fees_types' => ReactSelectResource::collection(CategoryFeesTypeEnum::collect()),
        ]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        try {
            $this->service->store(StoreCategoryDTO::fromValidated(
                $request->validated(),
                $request->file('icon'),
            ));

            return redirect()->route('dashboard.categories.index')->with('success', __('data saved successfully'));
        } catch (Throwable $throwable) {
            report($throwable);

            return redirect()->back()->with('error', __('something went wrong'));
        }
    }

    public function edit(Category $category): Response
    {
        $category = $this->service->show($category);

        return inertia('Dashboard/Categories/Edit', [
            'category' => CategoryResource::make($category),
            'categories' => CategoryResource::collection($this->service->getAllExcept($category)),
            'fees_types' => ReactSelectResource::collection(CategoryFeesTypeEnum::collect()),
        ]);
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        try {
            $this->service->update($category, UpdateCategoryDTO::fromValidated(
                $request->validated(),
                $request->file('icon'),
            ));

            return redirect()->route('dashboard.categories.index')->with('success', __('data updated successfully'));
        } catch (Throwable $throwable) {
            report($throwable);

            return redirect()->back()->with('error', __('something went wrong'));
        }
    }

    public function destroy(Category $category): RedirectResponse
    {
        try {
            $this->service->destroy($category);

            return redirect()->route('dashboard.categories.index')->with('success', __('data deleted successfully'));
        } catch (MarketplaceException $exception) {
            return redirect()->back()->with('error', $exception->getMessage());
        } catch (Throwable $throwable) {
            report($throwable);

            return redirect()->back()->with('error', __('something went wrong'));
        }
    }
}
