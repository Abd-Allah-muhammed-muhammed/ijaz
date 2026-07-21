<?php

namespace Modules\Marketplace\Contracts\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Modules\Marketplace\Models\Category;

interface CategoryRepositoryInterface
{
    public function paginateForDashboard(Request $request): LengthAwarePaginator;

    public function paginateForApi(Request $request): LengthAwarePaginator;

    public function paginateWithNoChildrenForApi(Request $request): LengthAwarePaginator;

    public function paginateChildrenForApi(Category $category, Request $request): LengthAwarePaginator;

    public function findById(int $id): Category;

    public function create(array $data): Category;

    public function update(Category $category, array $data): Category;

    public function delete(Category $category): void;

    public function loadForEdit(Category $category): Category;

    /**
     * @return Collection<int, Category>
     */
    public function getRootCategories(): Collection;

    /**
     * @return Collection<int, Category>
     */
    public function getLeafCategories(): Collection;

    /**
     * @return Collection<int, Category>
     */
    public function getAllWithTranslations(): Collection;

    /**
     * @return Collection<int, Category>
     */
    public function getAllExcept(Category $category): Collection;
}
