<?php

namespace App\Contracts\PanAnalytics;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface PanAnalyticsRepositoryInterface
{
    public function all(): Collection;

    public function paginateFiltered(?string $category, int $perPage): LengthAwarePaginator;

    public function truncate(): void;
}
