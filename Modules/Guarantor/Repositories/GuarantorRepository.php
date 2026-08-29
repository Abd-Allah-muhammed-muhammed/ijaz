<?php

namespace Modules\Guarantor\Repositories;

use App\Models\User;
use App\Support\LookupCache;
use App\Support\Phone;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Modules\Chat\Models\Conversation;
use Modules\Guarantor\Contracts\Repositories\GuarantorRepositoryInterface;
use Modules\Guarantor\DTOs\GuarantorFiltersData;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Models\GuarantorRequest;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class GuarantorRepository implements GuarantorRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): GuarantorRequest
    {
        return GuarantorRequest::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(GuarantorRequest $guarantorRequest, array $data): GuarantorRequest
    {
        $guarantorRequest->update($data);

        return $guarantorRequest->fresh();
    }

    public function findById(string $id): GuarantorRequest
    {
        return GuarantorRequest::query()
            ->with([
                'requester',
                'counterparty',
                'installments',
                'companyDetail.region',
                'companyDetail.city',
                'companyDetail.requesterBank',
                'companyDetail.counterpartyBank',
                'statusHistories.actor',
                'media',
            ])
            ->findOrFail($id);
    }

    public function findForUpdate(GuarantorRequest $guarantorRequest): GuarantorRequest
    {
        return GuarantorRequest::query()
            ->whereKey($guarantorRequest->getKey())
            ->lockForUpdate()
            ->firstOrFail();
    }

    public function findCounterpartyByPhone(string $phone): ?User
    {
        return User::query()
            ->where('phone', (string) Phone::make($phone))
            ->first();
    }

    public function listByRequester(Model $requester, int $perPage = 10): LengthAwarePaginator
    {
        return GuarantorRequest::query()
            ->where('requester_type', $requester::class)
            ->where('requester_id', $requester->getKey())
            ->with(['requester', 'counterparty', 'installments', 'media'])
            ->withCount(['installments'])
            ->latest()
            ->paginate($perPage);
    }

    public function listByCounterparty(Model $counterparty, int $perPage = 10): LengthAwarePaginator
    {
        return GuarantorRequest::query()
            ->where('counterparty_type', $counterparty::class)
            ->where('counterparty_id', $counterparty->getKey())
            ->with(['requester', 'counterparty', 'installments', 'media'])
            ->withCount(['installments'])
            ->latest()
            ->paginate($perPage);
    }

    public function listForActor(Model $actor, GuarantorFiltersData $filters): LengthAwarePaginator
    {
        return GuarantorRequest::query()
            ->when(true, function ($q) use ($actor, $filters) {
                if ($filters->role === 'requester') {
                    return $q->where('requester_type', $actor::class)
                        ->where('requester_id', $actor->getKey());
                }

                if ($filters->role === 'counterparty') {
                    return $q->where('counterparty_type', $actor::class)
                        ->where('counterparty_id', $actor->getKey());
                }

                return $q->forActor($actor);
            })
            ->when($filters->statuses, fn ($q) => $q->whereIn('status', $filters->statuses))
            ->when($filters->type, fn ($q, $v) => $q->where('type', $v))
            ->when($filters->search, fn ($q, $v) => $q->where('title', 'like', "%{$v}%"))
            ->when($filters->date_from, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($filters->date_to, fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->with(['requester', 'counterparty', 'installments', 'media'])
            ->withCount(['installments'])
            ->latest()
            ->paginate($filters->per_page);
    }

    public function listAll(int $perPage = 10): LengthAwarePaginator
    {
        return GuarantorRequest::query()
            ->with(['requester', 'counterparty', 'installments', 'media'])
            ->withCount(['installments'])
            ->latest()
            ->paginate($perPage);
    }

    public function paginateForDashboard(Request $request, int $perPage): LengthAwarePaginator
    {
        return GuarantorRequest::query()
            ->with(['requester', 'counterparty', 'installments', 'companyDetail', 'statusHistories', 'media'])
            ->withCount(['installments'])
            ->when($request->search, fn ($q) => $q->where('title', 'like', "%{$request->search}%"))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->type, fn ($q) => $q->where('type', $request->type))
            ->when($request->date_from, fn ($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->date_to, fn ($q) => $q->whereDate('created_at', '<=', $request->date_to))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @return array{total: int, pending_admin: int, in_progress: int, overdue: int, ended: int, cancelled: int}
     */
    public function getDashboardStats(): array
    {
        /** @var array{total: int, pending_admin: int, in_progress: int, overdue: int, ended: int, cancelled: int} */
        return LookupCache::rememberFor('stats:guarantor:dashboard', 30, fn (): array => [
            'total' => GuarantorRequest::count(),
            'pending_admin' => GuarantorRequest::where('status', GuarantorStatusEnum::PendingAdmin)->count(),
            'in_progress' => GuarantorRequest::where('status', GuarantorStatusEnum::InProgress)->count(),
            'overdue' => GuarantorRequest::where('status', GuarantorStatusEnum::Overdue)->count(),
            'ended' => GuarantorRequest::whereIn('status', GuarantorStatusEnum::endedAggregateValues())->count(),
            'cancelled' => GuarantorRequest::whereIn('status', GuarantorStatusEnum::cancelledAggregateValues())->count(),
        ]);
    }

    public function delete(GuarantorRequest $guarantorRequest): void
    {
        $guarantorRequest->delete();
    }

    public function deleteMedia(Media $media): void
    {
        $media->delete();
    }

    public function findConversation(GuarantorRequest $guarantorRequest): ?Conversation
    {
        $conversation = $guarantorRequest->conversation;

        return $conversation instanceof Conversation ? $conversation : null;
    }

    public function paginateConversationMessages(
        GuarantorRequest $guarantorRequest,
        int $perPage = 15,
        ?string $search = null,
    ): ?LengthAwarePaginator {
        $chat = $this->findConversation($guarantorRequest);

        if (! $chat) {
            return null;
        }

        $query = $chat->messages()
            ->latest()
            ->with(['sender', 'media', 'conversation.operation']);

        if ($search !== null && $search !== '') {
            $escaped = addcslashes($search, '%_\\');
            $query->where('content', 'like', '%'.$escaped.'%');
        }

        return $query
            ->paginate($perPage)
            ->withQueryString();
    }
}
