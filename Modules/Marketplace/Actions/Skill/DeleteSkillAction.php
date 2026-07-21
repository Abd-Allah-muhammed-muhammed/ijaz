<?php

namespace Modules\Marketplace\Actions\Skill;

use Modules\Marketplace\Contracts\Repositories\SkillRepositoryInterface;
use Modules\Marketplace\Models\Skill;

class DeleteSkillAction
{
    public function __construct(
        private readonly SkillRepositoryInterface $repository,
    ) {}

    public function handle(Skill $skill): void
    {
        $this->repository->delete($skill);
    }
}
