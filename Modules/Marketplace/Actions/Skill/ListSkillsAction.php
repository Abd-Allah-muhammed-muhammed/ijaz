<?php

namespace Modules\Marketplace\Actions\Skill;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Modules\Marketplace\Contracts\Repositories\SkillRepositoryInterface;

class ListSkillsAction
{
    public function __construct(
        private readonly SkillRepositoryInterface $repository,
    ) {}

    public function handle(Request $request): LengthAwarePaginator
    {
        return $this->repository->paginateForDashboard($request);
    }
}
