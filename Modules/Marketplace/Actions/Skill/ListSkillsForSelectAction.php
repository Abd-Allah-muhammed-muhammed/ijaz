<?php

namespace Modules\Marketplace\Actions\Skill;

use Illuminate\Database\Eloquent\Collection;
use Modules\Marketplace\Contracts\Repositories\SkillRepositoryInterface;
use Modules\Marketplace\Models\Skill;

class ListSkillsForSelectAction
{
    public function __construct(
        private readonly SkillRepositoryInterface $repository,
    ) {}

    /**
     * @return Collection<int, Skill>
     */
    public function handle(?string $search = null, int $categoryId = 0): Collection
    {
        return $this->repository->listForSelect($search, $categoryId);
    }
}
