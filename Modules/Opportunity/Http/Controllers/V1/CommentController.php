<?php

namespace Modules\Opportunity\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use MMAE\ApiResponse\Traits\HasApiResponse;
use Modules\Opportunity\Actions\Comment\AddCommentAction;
use Modules\Opportunity\Contracts\Repositories\OpportunityCommentRepositoryInterface;
use Modules\Opportunity\DTOs\CommentData;
use Modules\Opportunity\Http\Controllers\Concerns\AuthorizesOpportunityRequests;
use Modules\Opportunity\Http\Requests\StoreCommentRequest;
use Modules\Opportunity\Http\Resources\CommentCollection;
use Modules\Opportunity\Http\Resources\CommentResource;
use Modules\Opportunity\Models\Opportunity;
use Modules\Opportunity\Models\OpportunityComment;
use Throwable;

#[Group('Opportunity Comments')]
class CommentController extends Controller
{
    use AuthorizesOpportunityRequests;
    use HasApiResponse;

    public function __construct(
        private readonly OpportunityCommentRepositoryInterface $comments,
        private readonly AddCommentAction $addCommentAction,
    ) {}

    /**
     * List comments on an opportunity.
     *
     * @unauthenticated
     *
     * @urlParam opportunity string required The opportunity UUID.
     *
     * @queryParam per_page int Number of results per page. Example: 10
     *
     * @response 200 {
     *   "status": true,
     *   "data": {
     *     "items": [
     *       {
     *         "id": "01234567-89ab-cdef-0123-456789abcdef",
     *         "body": "Interested in this opportunity",
     *         "author": { "id": 2, "name": "Sara Ali" },
     *         "created_at": "2026-06-02T11:00:00+00:00"
     *       }
     *     ],
     *     "total": 1,
     *     "count": 1,
     *     "per_page": 10,
     *     "current_page": 1,
     *     "last_page": 1,
     *     "has_more_pages": false
     *   }
     * }
     */
    public function index(Request $request, Opportunity $opportunity): JsonResponse
    {
        return $this->successResponse(
            CommentCollection::make(
                $this->comments->listByOpportunity($opportunity, $request->integer('per_page', 10))
            )
        );
    }

    /**
     * Add a comment to an opportunity.
     *
     * @authenticated
     *
     * @throws Throwable
     *
     * @urlParam opportunity string required The opportunity UUID.
     *
     * @response 200 {
     *   "status": true,
     *   "data": {
     *     "id": "01234567-89ab-cdef-0123-456789abcdef",
     *     "body": "Interested in this opportunity",
     *     "author": { "id": 2, "name": "Sara Ali" },
     *     "created_at": "2026-06-02T11:00:00+00:00"
     *   }
     * }
     * @response 422 {
     *   "status": false,
     *   "message": "The given data was invalid.",
     *   "errors": { "body": ["The body field is required."] }
     * }
     */
    public function store(StoreCommentRequest $request, Opportunity $opportunity): JsonResponse
    {
        try {
            $this->authorize('create', [OpportunityComment::class, $opportunity]);

            $data = CommentData::fromRequest($request);
            $comment = $this->addCommentAction->handle($opportunity, $data, auth()->user());

            return $this->successResponse(CommentResource::make($comment));
        } catch (Throwable $throwable) {
            report($throwable);

            return $this->failedMessageResponse(__('something went wrong'));
        }
    }

    /**
     * Delete a comment.
     *
     * Only the comment author can delete their comment.
     *
     * @authenticated
     *
     * @urlParam opportunity string required The opportunity UUID.
     * @urlParam comment string required The comment UUID.
     *
     * @response 200 {
     *   "status": true,
     *   "message": "Comment deleted successfully"
     * }
     * @response 403 {
     *   "status": false,
     *   "message": "You are not authorized to perform this action"
     * }
     */
    public function destroy(Opportunity $opportunity, OpportunityComment $comment): JsonResponse
    {
        $this->authorizeOrFail('delete', [$comment, $opportunity], 'opportunity.unauthorized');

        $comment->delete();

        return $this->successMessageResponse(__('opportunity.comment_deleted_successfully'));
    }
}
