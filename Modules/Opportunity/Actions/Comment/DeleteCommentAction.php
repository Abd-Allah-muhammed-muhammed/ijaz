<?php

namespace Modules\Opportunity\Actions\Comment;

use Modules\Opportunity\Contracts\Repositories\OpportunityCommentRepositoryInterface;
use Modules\Opportunity\Models\OpportunityComment;

class DeleteCommentAction
{
    public function __construct(
        private readonly OpportunityCommentRepositoryInterface $repository,
    ) {}

    public function handle(OpportunityComment $comment): void
    {
        $this->repository->delete($comment);
    }
}
