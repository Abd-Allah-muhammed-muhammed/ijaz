<?php

namespace Modules\Guarantor\Actions\Chat;

use App\Models\Conversation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Models\GuarantorRequest;

class ListGuarantorChatsAction
{
    public function handle(Model $actor, int $perPage = 15): LengthAwarePaginator
    {
        return Conversation::query()
            ->where('operation_type', GuarantorRequest::class)
            ->where(function ($query) use ($actor) {
                $query
                    ->where(function ($query) use ($actor) {
                        $query->where('user1_id', $actor->getKey())
                            ->where('user1_type', $actor::class);
                    })
                    ->orWhere(function ($query) use ($actor) {
                        $query->where('user2_id', $actor->getKey())
                            ->where('user2_type', $actor::class);
                    });
            })
            ->whereHas('operation', function ($query) {
                $query->whereNotIn('status', [
                    GuarantorStatusEnum::RejectedByAdmin->value,
                    GuarantorStatusEnum::Rejected->value,
                    GuarantorStatusEnum::Ended->value,
                    GuarantorStatusEnum::Cancelled->value,
                    GuarantorStatusEnum::Refunded->value,
                ]);
            })
            ->with(['user1', 'user2', 'lastMassage', 'operation'])
            ->latest('last_message_at')
            ->paginate($perPage);
    }
}
