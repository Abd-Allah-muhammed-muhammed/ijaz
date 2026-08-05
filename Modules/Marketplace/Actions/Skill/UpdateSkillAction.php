<?php

namespace Modules\Marketplace\Actions\Skill;

use App\Support\LookupCache;
use Illuminate\Support\Facades\DB;
use Modules\Marketplace\Contracts\Repositories\SkillRepositoryInterface;
use Modules\Marketplace\DTOs\UpdateSkillDTO;
use Modules\Marketplace\Models\Skill;
use Throwable;

class UpdateSkillAction
{
    public function __construct(
        private readonly SkillRepositoryInterface $repository,
    ) {}

    /** @throws Throwable */
    public function handle(Skill $skill, UpdateSkillDTO $dto): Skill
    {
        $previousCategoryId = (int) $skill->category_id;

        $skill = DB::transaction(fn (): Skill => $this->repository->update($skill, [
            'category_id' => $dto->categoryId,
            'translations' => $dto->translations,
        ]));

        LookupCache::forgetScopedAllLocales('skills:by-category', $previousCategoryId);
        LookupCache::forgetScopedAllLocales('skills:by-category', $dto->categoryId);

        return $skill;
    }
}
