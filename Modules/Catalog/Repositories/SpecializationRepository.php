<?php

namespace Modules\Catalog\Repositories;

use App\Services\Normalize\Normalize;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Modules\Catalog\Contracts\Repositories\SpecializationRepositoryInterface;
use Modules\Catalog\Models\Specialization;
use Modules\Catalog\QueryFilters\Specialization\SpecializationFilters;

class SpecializationRepository implements SpecializationRepositoryInterface
{
    public function query(): Builder
    {
        return Specialization::withCount(['children'])->with(['translation']);
    }

    public function paginate(Request $request): LengthAwarePaginator
    {
        $filters = new SpecializationFilters($request);

        return $this->query()
            ->tap(fn (Builder $query) => $filters->apply($query))
            ->paginate($filters->perPage())
            ->withQueryString();
    }

    /**
     * @return Collection<int, Specialization>
     */
    public function getAll(Request $request): Collection
    {
        return Specialization::with(['translation'])
            ->when($request->parent_id,
                fn (Builder $query, mixed $value) => $query->where('parent_id', $value),
                fn (Builder $query) => $query->whereNull('parent_id'))
            ->when($request->search, function (Builder $query, mixed $value) {
                $normalized = Normalize::make($value, app()->getLocale())->toString();

                return $query->whereTranslationLike('normalized_title', "%{$normalized}%");
            })
            ->get();
    }

    public function create(array $data): Specialization
    {
        return Specialization::create($data);
    }

    public function update(Specialization $specialization, array $data): Specialization
    {
        $specialization->update($data);

        return $specialization->fresh();
    }

    public function delete(Specialization $specialization): void
    {
        if ($specialization->children()->exists()) {
            throw new \Exception(__('this specialization has subspecializations'));
        }
        $specialization->deleteIcon();
        $specialization->delete();
    }

    public function findById(int $id): Specialization
    {
        return Specialization::findOrFail($id);
    }

    public function find(int $id): ?Specialization
    {
        return Specialization::find($id);
    }

    /**
     * @return Collection<int, Specialization>
     */
    public function getRootSpecializations(?int $excludeId = null): Collection
    {
        return Specialization::with(['translation'])
            ->whereNull('parent_id')
            ->when($excludeId, fn (Builder $query) => $query->where('id', '!=', $excludeId))
            ->get();
    }
}
