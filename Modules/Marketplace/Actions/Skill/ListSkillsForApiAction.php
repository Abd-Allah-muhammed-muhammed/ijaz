<?php

namespace Modules\Marketplace\Actions\Skill;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Modules\Marketplace\Contracts\Repositories\SkillRepositoryInterface;

class ListSkillsForApiAction
{
    public function __construct(
        private readonly SkillRepositoryInterface $repository,
    ) {}

    public function handle(Request $request): LengthAwarePaginator
    {
        return $this->repository->paginateForApi($request);
    }

    public function handleForCategory(Request $request, int $categoryId): LengthAwarePaginator
    {
        return $this->repository->paginateForApi($request, $categoryId);
    }
}
