<?php

namespace Modules\Opportunity\Policies;

use Illuminate\Database\Eloquent\Model;
use Modules\Opportunity\Models\Opportunity;
use Modules\Opportunity\Models\OpportunityComment;

class OpportunityCommentPolicy
{
    public function create(Model $user, Opportunity $opportunity): bool
    {
        return true;
    }

    public function delete(Model $user, OpportunityComment $comment, Opportunity $opportunity): bool
    {
        return $comment->opportunity_id === $opportunity->id
            && $comment->author_type === $user::class
            && $comment->author_id === $user->getKey();
    }
}
