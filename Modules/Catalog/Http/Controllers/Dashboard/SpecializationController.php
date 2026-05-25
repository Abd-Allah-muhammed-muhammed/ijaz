<?php

namespace Modules\Catalog\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Response;
use Modules\Catalog\Contracts\Services\SpecializationServiceInterface;
use Modules\Catalog\DTOs\StoreSpecializationDTO;
use Modules\Catalog\DTOs\UpdateSpecializationDTO;
use Modules\Catalog\Http\Requests\Dashboard\SpecializationRequest;
use Modules\Catalog\Http\Resources\Dashboard\SpecializationCollection;
use Modules\Catalog\Http\Resources\Dashboard\SpecializationResource;
use Modules\Catalog\Models\Specialization;

class SpecializationController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly SpecializationServiceInterface $service,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:show specializations', only: ['index', 'show']),
            new Middleware('permission:create specializations', only: ['create', 'store']),
            new Middleware('permission:edit specializations', only: ['edit', 'update']),
            new Middleware('permission:delete specializations', only: ['destroy']),
        ];
    }

    public function index(Request $request): Response
    {
        $specializations = $this->service->index($request);

        return inertia('Dashboard/Specializations/Index', [
            'prams' => fn () => $request->all() ?: [],
            'rows' => fn () => SpecializationCollection::make($specializations),
        ]);
    }

    public function create(): Response
    {
        return inertia('Dashboard/Specializations/Create', [
            'specializations' => SpecializationResource::collection($this->service->getRootSpecializations()),
        ]);
    }

    public function store(SpecializationRequest $request): RedirectResponse
    {
        $dto = StoreSpecializationDTO::fromRequest($request);
        $this->service->store($dto);

        return redirect()->route('dashboard.specializations.index')->with('success', __('data saved successfully'));
    }

    public function edit(Specialization $specialization): Response
    {
        $specialization = $this->service->show($specialization);

        return inertia('Dashboard/Specializations/Edit', [
            'specialization' => SpecializationResource::make($specialization),
            'specializations' => SpecializationResource::collection(
                $this->service->getRootSpecializations(excludeId: $specialization->id)
            ),
        ]);
    }

    public function update(SpecializationRequest $request, Specialization $specialization): RedirectResponse
    {
        $dto = UpdateSpecializationDTO::fromRequest($request, $specialization);
        $this->service->update($specialization, $dto);

        return redirect()->route('dashboard.specializations.index')->with('success', __('data updated successfully'));
    }

    public function destroy(Specialization $specialization): RedirectResponse
    {
        $this->service->destroy($specialization);

        return redirect()->route('dashboard.specializations.index')->with('success', __('data deleted successfully'));
    }
}
