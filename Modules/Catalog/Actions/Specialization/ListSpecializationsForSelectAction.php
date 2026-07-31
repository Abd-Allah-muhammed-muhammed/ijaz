<?php

namespace Modules\Catalog\Actions\Specialization;

use App\Support\TranslationSearch;
use Illuminate\Database\Eloquent\Collection;
use Modules\Catalog\Models\Specialization;

class ListSpecializationsForSelectAction
{
    /**
     * @return Collection<int, Specialization>
     */
    public function handle(?string $search = null, int $parentId = 0): Collection
    {
        return Specialization::query()->withTranslation()
            ->when($search, fn ($query, $v) => TranslationSearch::apply($query, (string) $v))
            ->when($parentId, fn ($query, $v) => $query->where('parent_id', $v))
            ->get();
    }
}
