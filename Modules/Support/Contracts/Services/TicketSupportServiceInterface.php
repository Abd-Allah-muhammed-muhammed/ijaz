<?php

namespace Modules\Support\Contracts\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Modules\Support\DTOs\StoreTicketSupportDTO;
use Modules\Support\DTOs\UpdateTicketSupportStatusDTO;
use Modules\Support\Models\TicketSupport;

interface TicketSupportServiceInterface
{
    public function indexForUser(Model $user, int $perPage): LengthAwarePaginator;

    public function indexAll(Request $request): LengthAwarePaginator;

    /**
     * @param  array<int, string>  $relations
     */
    public function show(TicketSupport $ticket, array $relations = []): TicketSupport;

    public function store(StoreTicketSupportDTO $dto): TicketSupport;

    public function updateStatus(TicketSupport $ticket, UpdateTicketSupportStatusDTO $dto): TicketSupport;

    public function destroy(TicketSupport $ticket): void;
}
