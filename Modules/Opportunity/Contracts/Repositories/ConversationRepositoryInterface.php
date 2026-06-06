<?php

namespace Modules\Opportunity\Contracts\Repositories;

use App\Models\Conversation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Modules\Opportunity\Models\Opportunity;

interface ConversationRepositoryInterface
{
    public function findOrCreateForOpportunity(Opportunity $opportunity): Conversation;

    public function listForActor(Model $actor, int $perPage = 15): LengthAwarePaginator;
}
