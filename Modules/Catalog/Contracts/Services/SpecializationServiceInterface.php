<?php

namespace Modules\Catalog\Contracts\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Modules\Catalog\DTOs\StoreSpecializationDTO;
use Modules\Catalog\DTOs\UpdateSpecializationDTO;
use Modules\Catalog\Models\Specialization;

interface SpecializationServiceInterface
{
    public function index(Request $request): LengthAwarePaginator;

    /**
     * @return Collection<int, Specialization>
     */
    public function getAll(Request $request): Collection;

    public function store(StoreSpecializationDTO $dto): Specialization;

    public function update(Specialization $specialization, UpdateSpecializationDTO $dto): Specialization;

    public function destroy(Specialization $specialization): void;

    public function show(Specialization $specialization): Specialization;

    /**
     * @return Collection<int, Specialization>
     */
    public function getRootSpecializations(?int $excludeId = null): Collection;

    /**
     * @return Collection<int, Specialization>
     */
    public function listForSelect(?string $search = null, int $parentId = 0): Collection;
}
