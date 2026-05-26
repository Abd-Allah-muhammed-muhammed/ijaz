<?php

namespace Modules\Catalog\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Response;
use Modules\Catalog\Contracts\Services\ElectronicBrandServiceInterface;
use Modules\Catalog\DTOs\StoreElectronicBrandDTO;
use Modules\Catalog\DTOs\UpdateElectronicBrandDTO;
use Modules\Catalog\Http\Requests\Dashboard\ElectronicBrandRequest;
use Modules\Catalog\Http\Resources\Dashboard\ElectronicBrandCollection;
use Modules\Catalog\Http\Resources\Dashboard\ElectronicBrandResource;
use Modules\Catalog\Models\ElectronicBrand;

class ElectronicBrandController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly ElectronicBrandServiceInterface $service,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:show electronicBrands', only: ['index', 'show']),
            new Middleware('permission:create electronicBrands', only: ['create', 'store']),
            new Middleware('permission:edit electronicBrands', only: ['edit', 'update', 'updateStatus']),
            new Middleware('permission:delete electronicBrands', only: ['destroy']),
        ];
    }

    public function index(Request $request): Response
    {
        $brands = $this->service->index($request);

        return inertia('Dashboard/ElectronicBrands/Index', [
            'prams' => fn () => $request->all() ?: [],
            'rows' => fn () => ElectronicBrandCollection::make($brands),
        ]);
    }

    public function create(): Response
    {
        return inertia('Dashboard/ElectronicBrands/Create');
    }

    public function store(ElectronicBrandRequest $request): RedirectResponse
    {
        $dto = StoreElectronicBrandDTO::fromRequest($request);
        $this->service->store($dto);

        return redirect()->route('dashboard.electronic-brands.index')->with('success', __('data saved successfully'));
    }

    public function edit(ElectronicBrand $electronic_brand): Response
    {
        $electronic_brand = $this->service->show($electronic_brand);

        return inertia('Dashboard/ElectronicBrands/Edit', [
            'brand' => ElectronicBrandResource::make($electronic_brand),
        ]);
    }

    public function update(ElectronicBrandRequest $request, ElectronicBrand $electronic_brand): RedirectResponse
    {
        $dto = UpdateElectronicBrandDTO::fromRequest($request);
        $this->service->update($electronic_brand, $dto);

        return redirect()->route('dashboard.electronic-brands.index')->with('success', __('data updated successfully'));
    }

    public function updateStatus(Request $request, ElectronicBrand $electronic_brand): RedirectResponse
    {
        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $this->service->updateStatus($electronic_brand, (bool) $validated['is_active']);

        return redirect()->back()->with('success', __('data updated successfully'));
    }

    public function destroy(ElectronicBrand $electronic_brand): RedirectResponse
    {
        $this->service->destroy($electronic_brand);

        return redirect()->route('dashboard.electronic-brands.index')->with('success', __('data deleted successfully'));
    }
}
