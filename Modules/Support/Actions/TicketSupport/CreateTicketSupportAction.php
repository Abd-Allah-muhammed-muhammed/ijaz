<?php

namespace Modules\Support\Actions\TicketSupport;

use Illuminate\Support\Facades\DB;
use Modules\Support\Contracts\Repositories\TicketSupportRepositoryInterface;
use Modules\Support\DTOs\StoreTicketSupportDTO;
use Modules\Support\Enums\TicketSupportStatusEnum;
use Modules\Support\Models\TicketSupport;
use Throwable;

class CreateTicketSupportAction
{
    public function __construct(
        private readonly TicketSupportRepositoryInterface $repository,
        private readonly NotifyAdminsOfTicketCreatedAction $notifyAdminsOfTicketCreatedAction,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(StoreTicketSupportDTO $dto): TicketSupport
    {
        $ticket = DB::transaction(fn (): TicketSupport => $this->repository->create([
            ...$dto->toArray(),
            'status' => TicketSupportStatusEnum::Pending,
        ]));

        $this->notifyAdminsOfTicketCreatedAction->handle($ticket);

        return $ticket;
    }
}
