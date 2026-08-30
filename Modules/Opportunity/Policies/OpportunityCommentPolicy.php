<?php

namespace Modules\Opportunity\Policies;

use Illuminate\Database\Eloquent\Model;
use Modules\Opportunity\Enums\OpportunityStatusEnum;
use Modules\Opportunity\Models\Opportunity;
use Modules\Opportunity\Models\OpportunityComment;
use Modules\Opportunity\Policies\Concerns\AuthorizesOpportunityAuthor;

class OpportunityCommentPolicy
{
    use AuthorizesOpportunityAuthor;

    public function create(Model $user, Opportunity $opportunity): bool
    {
        if ($opportunity->status->isIn([
            OpportunityStatusEnum::PendingAdmin,
            OpportunityStatusEnum::RejectedByAdmin,
        ])) {
            return $this->isAuthor($user, $opportunity);
        }

        return true;
    }

    public function delete(Model $user, OpportunityComment $comment, Opportunity $opportunity): bool
    {
        return $comment->opportunity_id === $opportunity->id
            && $comment->author_type === $user::class
            && $comment->author_id === $user->getKey();
    }
}
