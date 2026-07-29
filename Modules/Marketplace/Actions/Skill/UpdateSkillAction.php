<?php

namespace Modules\Marketplace\Actions\Skill;

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
        return DB::transaction(fn (): Skill => $this->repository->update($skill, [
            'category_id' => $dto->categoryId,
            'translations' => $dto->translations,
        ]));
    }
}
