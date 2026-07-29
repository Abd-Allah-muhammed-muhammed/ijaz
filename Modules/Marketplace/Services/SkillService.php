<?php

namespace Modules\Marketplace\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Modules\Marketplace\Actions\Skill\DeleteSkillAction;
use Modules\Marketplace\Actions\Skill\ListSkillsAction;
use Modules\Marketplace\Actions\Skill\ListSkillsForApiAction;
use Modules\Marketplace\Actions\Skill\ListSkillsForSelectAction;
use Modules\Marketplace\Actions\Skill\ShowSkillAction;
use Modules\Marketplace\Actions\Skill\StoreSkillAction;
use Modules\Marketplace\Actions\Skill\UpdateSkillAction;
use Modules\Marketplace\DTOs\StoreSkillDTO;
use Modules\Marketplace\DTOs\UpdateSkillDTO;
use Modules\Marketplace\Models\Skill;

class SkillService
{
    public function __construct(
        private readonly ListSkillsAction $listAction,
        private readonly ListSkillsForApiAction $listForApiAction,
        private readonly ListSkillsForSelectAction $listForSelectAction,
        private readonly StoreSkillAction $storeAction,
        private readonly UpdateSkillAction $updateAction,
        private readonly DeleteSkillAction $deleteAction,
        private readonly ShowSkillAction $showAction,
    ) {}

    public function index(Request $request): LengthAwarePaginator
    {
        return $this->listAction->handle($request);
    }

    public function listForApi(Request $request): LengthAwarePaginator
    {
        return $this->listForApiAction->handle($request);
    }

    public function listForCategoryApi(Request $request, int $categoryId): LengthAwarePaginator
    {
        return $this->listForApiAction->handleForCategory($request, $categoryId);
    }

    public function store(StoreSkillDTO $dto): Skill
    {
        return $this->storeAction->handle($dto);
    }

    public function update(Skill $skill, UpdateSkillDTO $dto): Skill
    {
        return $this->updateAction->handle($skill, $dto);
    }

    public function destroy(Skill $skill): void
    {
        $this->deleteAction->handle($skill);
    }

    public function show(Skill $skill): Skill
    {
        return $this->showAction->handle($skill);
    }

    /**
     * @return Collection<int, Skill>
     */
    public function listForSelect(?string $search = null, int $categoryId = 0): Collection
    {
        return $this->listForSelectAction->handle($search, $categoryId);
    }
}
