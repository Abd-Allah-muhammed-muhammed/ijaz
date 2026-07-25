<?php

namespace App\Services\Provider;

use App\Actions\Provider\DeleteProviderAction;
use App\Actions\Provider\EditProviderAction;
use App\Actions\Provider\FindProviderByPhoneAction;
use App\Actions\Provider\FindProviderForApiAction;
use App\Actions\Provider\ListProvidersAction;
use App\Actions\Provider\ListProviderWalletTransactionsAction;
use App\Actions\Provider\ShowProviderAction;
use App\Actions\Provider\StoreProviderAction;
use App\Actions\Provider\UpdateProviderAction;
use App\Actions\Provider\UpdateProviderStatusAction;
use App\DTOs\Provider\StoreProviderDTO;
use App\DTOs\Provider\UpdateProviderDTO;
use App\DTOs\Provider\UpdateProviderStatusDTO;
use App\Models\Provider;
use App\Support\Phone;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Geo\Models\City;
use Modules\Geo\Models\Region;
use Modules\Geo\Services\CityService;
use Modules\Geo\Services\RegionService;
use Modules\Marketplace\Models\ProviderType;
use Modules\Marketplace\Services\ProviderTypeService;

class ProviderManagementService
{
    public function __construct(
        private readonly ListProvidersAction $listAction,
        private readonly ShowProviderAction $showAction,
        private readonly EditProviderAction $editAction,
        private readonly StoreProviderAction $storeAction,
        private readonly UpdateProviderAction $updateAction,
        private readonly DeleteProviderAction $deleteAction,
        private readonly UpdateProviderStatusAction $updateStatusAction,
        private readonly ListProviderWalletTransactionsAction $walletTransactionsAction,
        private readonly FindProviderForApiAction $findForApiAction,
        private readonly FindProviderByPhoneAction $findByPhoneAction,
        private readonly ProviderTypeService $providerTypeService,
        private readonly RegionService $regionService,
        private readonly CityService $cityService,
    ) {}

    public function index(Request $request): LengthAwarePaginator
    {
        return $this->listAction->handle($request);
    }

    public function show(Provider $provider): Provider
    {
        return $this->showAction->handle($provider);
    }

    public function edit(Provider $provider): Provider
    {
        return $this->editAction->handle($provider);
    }

    public function store(StoreProviderDTO $dto): Provider
    {
        return DB::transaction(fn (): Provider => $this->storeAction->handle($dto));
    }

    public function update(Provider $provider, UpdateProviderDTO $dto): Provider
    {
        return DB::transaction(fn (): Provider => $this->updateAction->handle($provider, $dto));
    }

    public function destroy(Provider $provider): void
    {
        DB::transaction(fn () => $this->deleteAction->handle($provider));
    }

    public function updateStatus(Provider $provider, UpdateProviderStatusDTO $dto): Provider
    {
        return DB::transaction(fn (): Provider => $this->updateStatusAction->handle($provider, $dto));
    }

    public function listWalletTransactions(Provider $provider, ?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        return $this->walletTransactionsAction->handle($provider, $search, $perPage);
    }

    public function findForApi(int|string $providerId): ?Provider
    {
        return $this->findForApiAction->handle($providerId);
    }

    public function findByPhone(Phone $phone, ?int $categoryId = null): ?Provider
    {
        return $this->findByPhoneAction->handle($phone, $categoryId);
    }

    /**
     * ProviderType dropdown for create/edit — same query as Marketplace API list.
     *
     * @return Collection<int, ProviderType>
     */
    public function getProviderTypesForDropdown(): Collection
    {
        return $this->providerTypeService->listForApi();
    }

    /**
     * @return Collection<int, Region>
     */
    public function getRegionsForDropdown(): Collection
    {
        return $this->regionService->listForSelect();
    }

    /**
     * @return Collection<int, City>
     */
    public function getCitiesForDropdown(): Collection
    {
        return $this->cityService->listForSelect();
    }
}
