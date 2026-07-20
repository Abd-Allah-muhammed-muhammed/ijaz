<?php

namespace Modules\Geo\Contracts\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Modules\Geo\Models\Nationality;

interface NationalityRepositoryInterface
{
    public function paginate(Request $request): LengthAwarePaginator;

    public function findById(int $id): Nationality;

    public function create(array $translations): Nationality;

    public function update(Nationality $nationality, array $translations): Nationality;

    public function delete(Nationality $nationality): void;
}
