<?php

namespace Modules\Marketplace\Actions\Skill;

use App\Support\TranslationSearch;
use Illuminate\Database\Eloquent\Collection;
use Modules\Marketplace\Models\Skill;

class ListSkillsForSelectAction
{
    /**
     * @return Collection<int, Skill>
     */
    public function handle(?string $search = null, int $categoryId = 0): Collection
    {
        return Skill::query()->withTranslation()
            ->when($search, fn ($query, $v) => TranslationSearch::apply($query, (string) $v))
            ->where('category_id', $categoryId)->get();
    }
}
