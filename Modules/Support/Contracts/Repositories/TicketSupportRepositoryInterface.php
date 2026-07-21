<?php

namespace Modules\Support\Contracts\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Modules\Support\Enums\TicketSupportStatusEnum;
use Modules\Support\Models\TicketSupport;

interface TicketSupportRepositoryInterface
{
    public function paginateForUser(Model $user, int $perPage): LengthAwarePaginator;

    public function paginateAll(int $perPage): LengthAwarePaginator;

    public function findById(int $id): TicketSupport;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): TicketSupport;

    public function updateStatus(TicketSupport $ticket, TicketSupportStatusEnum $status): TicketSupport;

    public function delete(TicketSupport $ticket): void;

    /**
     * @param  array<int, string>  $relations
     */
    public function loadForShow(TicketSupport $ticket, array $relations): TicketSupport;
}
