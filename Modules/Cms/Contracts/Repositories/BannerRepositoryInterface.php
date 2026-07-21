<?php

namespace Modules\Cms\Contracts\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Modules\Cms\Models\Banner;

interface BannerRepositoryInterface
{
    public function paginate(Request $request): LengthAwarePaginator;

    public function findById(int $id): Banner;

    public function create(array $data): Banner;

    public function update(Banner $banner, array $data): Banner;

    public function delete(Banner $banner): void;

    /**
     * @return Collection<int, Banner>
     */
    public function all(): Collection;
}
