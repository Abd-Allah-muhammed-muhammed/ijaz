<?php

namespace App\Http\Controllers\Dashboard;

use App\DTOs\Provider\StoreProviderDTO;
use App\DTOs\Provider\UpdateProviderDTO;
use App\DTOs\Provider\UpdateProviderStatusDTO;
use App\Enums\ProviderTypeFilesEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\ProviderRequest;
use App\Http\Requests\Dashboard\ProviderStatusRequest;
use App\Http\Resources\Dashboard\ProviderCollection;
use App\Http\Resources\Dashboard\ProviderResource;
use App\Models\Provider;
use App\Services\Provider\ProviderManagementService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Response;
use Modules\Geo\Http\Resources\Dashboard\CityResource;
use Modules\Geo\Http\Resources\Dashboard\RegionResource;
use Modules\Marketplace\Http\Resources\Dashboard\ProviderTypeResource;
use Modules\Wallet\Http\Resources\Dashboard\WalletTransactionCollection;
use Throwable;

class ProviderController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly ProviderManagementService $providerService,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:show providers', only: ['index', 'show']),
            new Middleware('permission:create providers', only: ['create', 'store']),
            new Middleware('permission:edit providers', only: ['edit', 'update']),
            new Middleware('permission:delete providers', only: ['destroy']),
            new Middleware('permission:process providers', only: ['updateStatus']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        return inertia('Dashboard/Providers/Index', [
            'prams' => $request->all() ?: [],
            'rows' => ProviderCollection::make($this->providerService->index($request)),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Provider $provider)
    {
        $provider = $this->providerService->show($provider);

        return inertia('Dashboard/Providers/Show', [
            'provider' => function () use ($provider) {
                return ProviderResource::make($provider);
            },
            'transactions' => WalletTransactionCollection::make(
                $this->providerService->listWalletTransactions(
                    $provider,
                    $request->input('search'),
                    $request->integer('per_page', 25),
                )
            ),
            'prams' => fn () => $request->all() ?: [],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Provider $provider): Response
    {
        return inertia('Dashboard/Providers/Edit', [
            'row' => ProviderResource::make($this->providerService->edit($provider)),
            'types' => ProviderTypeResource::collection($this->providerService->getProviderTypesForDropdown()),
            'regions' => RegionResource::collection($this->providerService->getRegionsForDropdown()),
            'cities' => CityResource::collection($this->providerService->getCitiesForDropdown()),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @throws Throwable
     */
    public function update(ProviderRequest $request, Provider $provider): RedirectResponse
    {
        // Preserve prior behavior: update failures rethrow (the old catch's
        // report/redirect after `throw $e` was unreachable dead code).
        $this->providerService->update($provider, UpdateProviderDTO::fromValidated(
            $request->validated(),
            $request->file('logo'),
            $this->mediaFilesFromRequest($request),
        ));

        return to_route('dashboard.providers.index')->with('success', __('data updated successfully'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @throws Throwable
     */
    public function store(ProviderRequest $request): RedirectResponse
    {
        try {
            $this->providerService->store(StoreProviderDTO::fromValidated(
                $request->validated(),
                $request->file('logo'),
                $this->mediaFilesFromRequest($request),
            ));
        } catch (Exception $e) {
            report($e);

            return redirect()->back()->with('error', __('something went wrong'));
        }

        return to_route('dashboard.providers.index')->with('success', __('data saved successfully'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return inertia('Dashboard/Providers/Create', [
            'types' => ProviderTypeResource::collection($this->providerService->getProviderTypesForDropdown()),
            'regions' => RegionResource::collection($this->providerService->getRegionsForDropdown()),
            'cities' => CityResource::collection($this->providerService->getCitiesForDropdown()),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @throws Throwable
     */
    public function destroy(Provider $provider): RedirectResponse
    {
        try {
            $this->providerService->destroy($provider);
        } catch (Throwable $e) {
            report($e);

            return redirect()->back()->with('error', __('something went wrong'));
        }

        return redirect()->route('dashboard.providers.index')
            ->with('success', __('data deleted successfully'));
    }

    public function updateStatus(ProviderStatusRequest $request, Provider $provider): RedirectResponse
    {
        $this->providerService->updateStatus($provider, UpdateProviderStatusDTO::fromValidated($request->validated()));

        return to_route('dashboard.providers.index')->with('success', __('data saved successfully'));
    }

    /**
     * @return array<string, UploadedFile>
     */
    private function mediaFilesFromRequest(ProviderRequest $request): array
    {
        $files = [];

        foreach (ProviderTypeFilesEnum::cases() as $file) {
            if ($request->hasFile($file->value)) {
                $files[$file->value] = $request->file($file->value);
            }
        }

        return $files;
    }
}
