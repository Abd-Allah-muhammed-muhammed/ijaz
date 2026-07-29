<?php

namespace Modules\Marketplace\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Response;
use Modules\Marketplace\DTOs\StoreProviderTypeDTO;
use Modules\Marketplace\DTOs\UpdateProviderTypeDTO;
use Modules\Marketplace\Exceptions\MarketplaceException;
use Modules\Marketplace\Http\Requests\Dashboard\ProviderTypeRequest;
use Modules\Marketplace\Http\Resources\Dashboard\CategoryCollection;
use Modules\Marketplace\Http\Resources\Dashboard\ProviderTypeCollection;
use Modules\Marketplace\Http\Resources\Dashboard\ProviderTypeResource;
use Modules\Marketplace\Models\ProviderType;
use Modules\Marketplace\Services\CategoryService;
use Modules\Marketplace\Services\ProviderTypeService;
use Throwable;

class ProviderTypeController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly ProviderTypeService $service,
        private readonly CategoryService $categoryService,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:show providerTypes', only: ['index', 'show']),
            new Middleware('permission:create providerTypes', only: ['create', 'store']),
            new Middleware('permission:edit providerTypes', only: ['edit', 'update']),
            new Middleware('permission:delete providerTypes', only: ['destroy']),
        ];
    }

    public function index(Request $request): Response
    {
        return inertia('Dashboard/ProviderTypes/Index', [
            'prams' => $request->all() ?: [],
            'rows' => ProviderTypeCollection::make($this->service->index($request)),
        ]);
    }

    public function create(): Response
    {
        return inertia('Dashboard/ProviderTypes/Create', [
            'categories' => CategoryCollection::make($this->categoryService->getRootCategories()),
        ]);
    }

    /**
     * @throws Throwable
     */
    public function store(ProviderTypeRequest $request): RedirectResponse
    {
        try {
            $this->service->store(StoreProviderTypeDTO::fromValidated(
                $request->validated(),
                $request->file('image'),
            ));

            return redirect()->route('dashboard.provider-types.index')->with('success', __('data saved successfully'));
        } catch (Throwable $th) {
            report($th);
            throw $th;
        }
    }

    public function edit(ProviderType $providerType): Response
    {
        $providerType = $this->service->show($providerType);

        return inertia('Dashboard/ProviderTypes/Edit', [
            'row' => ProviderTypeResource::make($providerType),
            'categories' => CategoryCollection::make($this->categoryService->getRootCategories()),
        ]);
    }

    /**
     * @throws Throwable
     */
    public function update(ProviderTypeRequest $request, ProviderType $providerType): RedirectResponse
    {
        try {
            $this->service->update($providerType, UpdateProviderTypeDTO::fromValidated(
                $request->validated(),
                $request->file('image'),
            ));

            return redirect()->route('dashboard.provider-types.index')->with('success', __('data saved successfully'));
        } catch (Throwable $th) {
            report($th);

            return redirect()->back()->with('error', __('something went wrong'));
        }
    }

    /**
     * @throws Throwable
     */
    public function destroy(ProviderType $providerType): RedirectResponse
    {
        try {
            $this->service->destroy($providerType);

            return redirect()->route('dashboard.provider-types.index')->with('success', __('data deleted successfully'));
        } catch (MarketplaceException $exception) {
            return redirect()->back()->with('error', $exception->getMessage());
        } catch (Throwable $th) {
            report($th);

            return redirect()->back()->with('error', __('something went wrong'));
        }
    }
}
