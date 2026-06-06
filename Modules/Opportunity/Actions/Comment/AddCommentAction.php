<?php

namespace Modules\Opportunity\Actions\Comment;

use Illuminate\Database\Eloquent\Model;
use Modules\Opportunity\Contracts\Repositories\OpportunityCommentRepositoryInterface;
use Modules\Opportunity\DTOs\CommentData;
use Modules\Opportunity\Models\Opportunity;
use Modules\Opportunity\Models\OpportunityComment;

class AddCommentAction
{
    public function __construct(
        private readonly OpportunityCommentRepositoryInterface $comments,
    ) {}

    public function handle(Opportunity $opportunity, CommentData $data, Model $author): OpportunityComment
    {
        $comment = $this->comments->create([
            'opportunity_id' => $opportunity->id,
            'body' => $data->body,
            'author_type' => $author::class,
            'author_id' => $author->getKey(),
        ]);

        $comment->load(['author']);

        return $comment;
    }
}
