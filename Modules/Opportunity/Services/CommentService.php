<?php

namespace Modules\Opportunity\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Modules\Opportunity\Actions\Comment\AddCommentAction;
use Modules\Opportunity\Actions\Comment\DeleteCommentAction;
use Modules\Opportunity\Contracts\Repositories\OpportunityCommentRepositoryInterface;
use Modules\Opportunity\DTOs\CommentData;
use Modules\Opportunity\Models\Opportunity;
use Modules\Opportunity\Models\OpportunityComment;

class CommentService
{
    public function __construct(
        private readonly OpportunityCommentRepositoryInterface $repository,
        private readonly AddCommentAction $addAction,
        private readonly DeleteCommentAction $deleteAction,
    ) {}

    public function listByOpportunity(Opportunity $opportunity, int $perPage = 10): LengthAwarePaginator
    {
        return $this->repository->listByOpportunity($opportunity, $perPage);
    }

    public function add(Opportunity $opportunity, CommentData $data, Model $author): OpportunityComment
    {
        return $this->addAction->handle($opportunity, $data, $author);
    }

    public function delete(OpportunityComment $comment): void
    {
        $this->deleteAction->handle($comment);
    }

    public function deleteForDashboard(OpportunityComment $comment): void
    {
        $this->deleteAction->handle($comment);
    }
}
