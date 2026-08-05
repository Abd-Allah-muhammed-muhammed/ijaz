<?php

namespace Modules\Catalog\Actions\ElectronicBrand;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Modules\Catalog\Models\ElectronicBrand;

class ListAllElectronicBrandsAction
{
    public function __construct(
        private readonly ListElectronicBrandsForSelectAction $listForSelectAction,
    ) {}

    /**
     * Active brands for API — same shape as select (empty search uses LookupCache).
     *
     * @return Collection<int, ElectronicBrand>
     */
    public function handle(Request $request): Collection
    {
        return $this->listForSelectAction->handle(
            filled($request->search) ? (string) $request->search : null,
        );
    }
}
