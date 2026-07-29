<?php

namespace Modules\Marketplace\Actions\Skill;

use Modules\Marketplace\Contracts\Repositories\SkillRepositoryInterface;
use Modules\Marketplace\Models\Skill;

class ShowSkillAction
{
    public function __construct(
        private readonly SkillRepositoryInterface $repository,
    ) {}

    public function handle(Skill $skill): Skill
    {
        return $this->repository->loadForEdit($skill);
    }
}
