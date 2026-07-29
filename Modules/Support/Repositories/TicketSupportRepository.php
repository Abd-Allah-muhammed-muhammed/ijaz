<?php

namespace Modules\Support\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Modules\Support\Contracts\Repositories\TicketSupportRepositoryInterface;
use Modules\Support\Enums\TicketSupportStatusEnum;
use Modules\Support\Exceptions\TicketSupportNotDeletableException;
use Modules\Support\Models\TicketSupport;

class TicketSupportRepository implements TicketSupportRepositoryInterface
{
    public function paginateForUser(Model $user, int $perPage): LengthAwarePaginator
    {
        return TicketSupport::query()
            ->whereMorphedTo('user', $user)
            ->latest()
            ->paginate($perPage);
    }

    public function paginateAll(Request $request): LengthAwarePaginator
    {
        return TicketSupport::query()
            ->when($request->input('search'), function (Builder $query, mixed $search) {
                return $query->where('title', 'like', '%'.((string) $search).'%');
            })
            ->latest()
            ->with(['operation', 'user'])
            ->paginate($request->integer('perPage', 10));
    }

    public function findById(int $id): TicketSupport
    {
        return TicketSupport::query()->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): TicketSupport
    {
        return TicketSupport::query()->create($data);
    }

    public function updateStatus(TicketSupport $ticket, TicketSupportStatusEnum $status): TicketSupport
    {
        $ticket->update(['status' => $status]);

        return $ticket;
    }

    /**
     * @throws TicketSupportNotDeletableException
     */
    public function delete(TicketSupport $ticket): void
    {
        if ($ticket->status->isNot(TicketSupportStatusEnum::Pending)) {
            throw new TicketSupportNotDeletableException(trans('you can not delete this ticket'));
        }

        $ticket->delete();
    }

    /**
     * @param  array<int, string>  $relations
     */
    public function loadForShow(TicketSupport $ticket, array $relations): TicketSupport
    {
        return $ticket->load($relations);
    }
}
