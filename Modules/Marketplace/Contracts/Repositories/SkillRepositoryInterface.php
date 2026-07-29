<?php

namespace Modules\Marketplace\Contracts\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Modules\Marketplace\Models\Skill;

interface SkillRepositoryInterface
{
    public function paginateForDashboard(Request $request): LengthAwarePaginator;

    public function paginateForApi(Request $request, ?int $categoryId = null): LengthAwarePaginator;

    public function findById(int $id): Skill;

    public function create(array $data): Skill;

    public function update(Skill $skill, array $data): Skill;

    public function delete(Skill $skill): void;

    public function loadForEdit(Skill $skill): Skill;
}
