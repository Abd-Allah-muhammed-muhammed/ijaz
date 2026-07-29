<?php

namespace Modules\Marketplace\Actions\Skill;

use Illuminate\Support\Facades\DB;
use Modules\Marketplace\Contracts\Repositories\SkillRepositoryInterface;
use Modules\Marketplace\DTOs\StoreSkillDTO;
use Modules\Marketplace\Models\Skill;
use Throwable;

class StoreSkillAction
{
    public function __construct(
        private readonly SkillRepositoryInterface $repository,
    ) {}

    /** @throws Throwable */
    public function handle(StoreSkillDTO $dto): Skill
    {
        return DB::transaction(fn (): Skill => $this->repository->create([
            'category_id' => $dto->categoryId,
            'translations' => $dto->translations,
        ]));
    }
}
