<?php

namespace Modules\Marketplace\Actions\Skill;

use App\Support\LookupCache;
use Modules\Marketplace\Contracts\Repositories\SkillRepositoryInterface;
use Modules\Marketplace\Models\Skill;

class DeleteSkillAction
{
    public function __construct(
        private readonly SkillRepositoryInterface $repository,
    ) {}

    public function handle(Skill $skill): void
    {
        $categoryId = (int) $skill->category_id;

        $this->repository->delete($skill);

        LookupCache::forgetScopedAllLocales('skills:by-category', $categoryId);
    }
}
