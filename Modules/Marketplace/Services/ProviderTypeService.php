<?php

namespace Modules\Marketplace\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Modules\Marketplace\Actions\ProviderType\DeleteProviderTypeAction;
use Modules\Marketplace\Actions\ProviderType\ListProviderTypesAction;
use Modules\Marketplace\Actions\ProviderType\ListProviderTypesForApiAction;
use Modules\Marketplace\Actions\ProviderType\ShowProviderTypeAction;
use Modules\Marketplace\Actions\ProviderType\StoreProviderTypeAction;
use Modules\Marketplace\Actions\ProviderType\UpdateProviderTypeAction;
use Modules\Marketplace\DTOs\StoreProviderTypeDTO;
use Modules\Marketplace\DTOs\UpdateProviderTypeDTO;
use Modules\Marketplace\Models\ProviderType;

class ProviderTypeService
{
    public function __construct(
        private readonly ListProviderTypesAction $listAction,
        private readonly ListProviderTypesForApiAction $listForApiAction,
        private readonly StoreProviderTypeAction $storeAction,
        private readonly UpdateProviderTypeAction $updateAction,
        private readonly DeleteProviderTypeAction $deleteAction,
        private readonly ShowProviderTypeAction $showAction,
    ) {}

    public function index(Request $request): LengthAwarePaginator
    {
        return $this->listAction->handle($request);
    }

    /** @return Collection<int, ProviderType> */
    public function listForApi(): Collection
    {
        return $this->listForApiAction->handle();
    }

    public function store(StoreProviderTypeDTO $dto): ProviderType
    {
        return $this->storeAction->handle($dto);
    }

    public function update(ProviderType $providerType, UpdateProviderTypeDTO $dto): ProviderType
    {
        return $this->updateAction->handle($providerType, $dto);
    }

    public function destroy(ProviderType $providerType): void
    {
        $this->deleteAction->handle($providerType);
    }

    public function show(ProviderType $providerType): ProviderType
    {
        return $this->showAction->handle($providerType);
    }
}
