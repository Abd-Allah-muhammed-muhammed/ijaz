<?php

namespace Modules\Marketplace\Actions\Skill;

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
            ->when($search, fn ($query, $v) => $query->whereTranslationLike('title', "%{$v}%"))
            ->where('category_id', $categoryId)->get();
    }
}
