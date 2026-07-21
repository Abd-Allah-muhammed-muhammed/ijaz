<?php

namespace Modules\Support\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Modules\Support\Actions\TicketSupport\CreateTicketSupportAction;
use Modules\Support\Actions\TicketSupport\DeleteTicketSupportAction;
use Modules\Support\Actions\TicketSupport\ListAllTicketSupportsAction;
use Modules\Support\Actions\TicketSupport\ListTicketSupportsAction;
use Modules\Support\Actions\TicketSupport\ShowTicketSupportAction;
use Modules\Support\Actions\TicketSupport\UpdateTicketSupportStatusAction;
use Modules\Support\Contracts\Services\TicketSupportServiceInterface;
use Modules\Support\DTOs\StoreTicketSupportDTO;
use Modules\Support\DTOs\UpdateTicketSupportStatusDTO;
use Modules\Support\Models\TicketSupport;

class TicketSupportService implements TicketSupportServiceInterface
{
    public function __construct(
        private readonly ListTicketSupportsAction $listAction,
        private readonly ListAllTicketSupportsAction $listAllAction,
        private readonly ShowTicketSupportAction $showAction,
        private readonly CreateTicketSupportAction $createAction,
        private readonly UpdateTicketSupportStatusAction $updateStatusAction,
        private readonly DeleteTicketSupportAction $deleteAction,
    ) {}

    public function indexForUser(Model $user, int $perPage): LengthAwarePaginator
    {
        return $this->listAction->handle($user, $perPage);
    }

    public function indexAll(int $perPage): LengthAwarePaginator
    {
        return $this->listAllAction->handle($perPage);
    }

    /**
     * @param  array<int, string>  $relations
     */
    public function show(TicketSupport $ticket, array $relations = []): TicketSupport
    {
        return $this->showAction->handle($ticket, $relations);
    }

    public function store(StoreTicketSupportDTO $dto): TicketSupport
    {
        return $this->createAction->handle($dto);
    }

    public function updateStatus(TicketSupport $ticket, UpdateTicketSupportStatusDTO $dto): TicketSupport
    {
        return $this->updateStatusAction->handle($ticket, $dto);
    }

    public function destroy(TicketSupport $ticket): void
    {
        $this->deleteAction->handle($ticket);
    }
}
