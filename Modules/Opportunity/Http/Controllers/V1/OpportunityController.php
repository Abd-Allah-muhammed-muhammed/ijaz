<?php

namespace Modules\Opportunity\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use MMAE\ApiResponse\Traits\HasApiResponse;
use Modules\Opportunity\DTOs\OpportunityData;
use Modules\Opportunity\Exceptions\OpportunityException;
use Modules\Opportunity\Http\Controllers\Concerns\AuthorizesOpportunityRequests;
use Modules\Opportunity\Http\Requests\StoreOpportunityRequest;
use Modules\Opportunity\Http\Requests\UpdateOpportunityRequest;
use Modules\Opportunity\Http\Resources\OpportunityCollection;
use Modules\Opportunity\Http\Resources\OpportunityResource;
use Modules\Opportunity\Models\Opportunity;
use Modules\Opportunity\Services\OpportunityService;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

#[Group('Opportunities')]
class OpportunityController extends Controller
{
    use AuthorizesOpportunityRequests;
    use HasApiResponse;

    public function __construct(
        private readonly OpportunityService $service,
    ) {}

    /**
     * List all public opportunities.
     *
     * Returns a paginated list of all active opportunities available publicly.
     *
     * @unauthenticated
     *
     * @queryParam per_page int Number of results per page. Example: 10
     *
     * @response 200 {
     *   "status": true,
     *   "data": {
     *     "items": [
     *       {
     *         "id": "01234567-89ab-cdef-0123-456789abcdef",
     *         "title": "Looking for a backend developer",
     *         "description": "We need a Laravel developer for a 3 months project",
     *         "budget": 5000.00,
     *         "status": { "value": "new", "label": "New", "color": "primary" },
     *         "author": { "id": 1, "name": "Ahmed Mohamed" },
     *         "region": { "id": 1, "title": "Riyadh" },
     *         "city": { "id": 5, "title": "Riyadh City" },
     *         "offers_count": 3,
     *         "comments_count": 7,
     *         "media": [],
     *         "expires_at": "2026-12-31T00:00:00+00:00",
     *         "created_at": "2026-06-01T10:00:00+00:00"
     *       }
     *     ],
     *     "total": 50,
     *     "count": 10,
     *     "per_page": 10,
     *     "current_page": 1,
     *     "last_page": 5,
     *     "has_more_pages": true
     *   }
     * }
     */
    public function all(Request $request): JsonResponse
    {
        return $this->successResponse(
            OpportunityCollection::make(
                $this->service->listPublic($request->integer('per_page', 10))
            )
        );
    }

    /**
     * List authenticated user's opportunities.
     *
     * Returns a paginated list of opportunities created by the authenticated user or provider.
     *
     * @authenticated
     *
     * @queryParam per_page int Number of results per page. Example: 10
     *
     * @response 200 {
     *   "status": true,
     *   "data": {
     *     "items": [],
     *     "total": 0,
     *     "count": 0,
     *     "per_page": 10,
     *     "current_page": 1,
     *     "last_page": 1,
     *     "has_more_pages": false
     *   }
     * }
     * @response 401 {
     *   "status": false,
     *   "message": "Unauthenticated."
     * }
     */
    public function index(Request $request): JsonResponse
    {
        return $this->successResponse(
            OpportunityCollection::make(
                $this->service->listByActor(auth()->user(), $request->integer('per_page', 10))
            )
        );
    }

    /**
     * Create a new opportunity.
     *
     * @authenticated
     *
     * @throws Throwable
     *
     * @response 200 {
     *   "status": true,
     *   "data": {
     *     "id": "01234567-89ab-cdef-0123-456789abcdef",
     *     "title": "Looking for a backend developer",
     *     "description": "We need a Laravel developer for a 3 months project",
     *     "budget": 5000.00,
     *     "status": { "value": "new", "label": "New", "color": "primary" },
     *     "author": { "id": 1, "name": "Ahmed Mohamed" },
     *     "media": [],
     *     "created_at": "2026-06-01T10:00:00+00:00"
     *   }
     * }
     * @response 422 {
     *   "status": false,
     *   "message": "The given data was invalid.",
     *   "errors": { "title": ["The title field is required."] }
     * }
     */
    public function store(StoreOpportunityRequest $request): JsonResponse
    {
        try {
            $data = OpportunityData::fromRequest($request);
            $opportunity = $this->service->create($data, auth()->user(), $request);

            return $this->successResponse(OpportunityResource::make($opportunity));
        } catch (OpportunityException $e) {
            throw $e;
        } catch (Throwable $throwable) {
            report($throwable);

            return $this->failedMessageResponse(__('something went wrong'));
        }
    }

    /**
     * Show a single opportunity.
     *
     * @unauthenticated
     *
     * @urlParam opportunity string required The opportunity UUID. Example: 01234567-89ab-cdef-0123-456789abcdef
     *
     * @response 200 {
     *   "status": true,
     *   "data": {
     *     "id": "01234567-89ab-cdef-0123-456789abcdef",
     *     "title": "Looking for a backend developer",
     *     "description": "We need a Laravel developer for a 3 months project",
     *     "budget": 5000.00,
     *     "status": { "value": "new", "label": "New", "color": "primary" },
     *     "author": { "id": 1, "name": "Ahmed Mohamed" },
     *     "offers_count": 2,
     *     "comments_count": 5,
     *     "media": [],
     *     "created_at": "2026-06-01T10:00:00+00:00"
     *   }
     * }
     * @response 404 {
     *   "status": false,
     *   "message": "No query results for model."
     * }
     */
    public function show(Opportunity $opportunity): JsonResponse
    {
        return $this->successResponse(
            OpportunityResource::make($this->service->loadForShow($opportunity))
        );
    }

    /**
     * Update an opportunity.
     *
     * Only the opportunity author can update. Supports partial updates.
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
     *     "title": "Updated title",
     *     "status": { "value": "new", "label": "New", "color": "primary" }
     *   }
     * }
     * @response 403 {
     *   "status": false,
     *   "message": "You are not authorized to perform this action"
     * }
     */
    public function update(UpdateOpportunityRequest $request, Opportunity $opportunity): JsonResponse
    {
        try {
            $this->authorizeOrFail('update', $opportunity, 'opportunity.unauthorized');

            $data = OpportunityData::fromRequest($request);
            $opportunity = $this->service->update($opportunity, $data, $request);

            return $this->successResponse(OpportunityResource::make($opportunity));
        } catch (OpportunityException $e) {
            throw $e;
        } catch (Throwable $throwable) {
            report($throwable);

            if ($throwable instanceof HttpExceptionInterface) {
                return $this->failedMessageResponse($throwable->getMessage(), $throwable->getStatusCode());
            }

            return $this->failedMessageResponse(__('something went wrong'));
        }
    }

    /**
     * Delete an opportunity.
     *
     * Only the author can delete, and only when status is New.
     *
     * @authenticated
     *
     * @throws Throwable
     *
     * @urlParam opportunity string required The opportunity UUID.
     *
     * @response 200 {
     *   "status": true,
     *   "message": "Opportunity deleted successfully"
     * }
     * @response 403 {
     *   "status": false,
     *   "message": "You can only delete opportunities with status New"
     * }
     */
    public function destroy(Opportunity $opportunity): JsonResponse
    {
        try {
            $this->authorizeOrFail('update', $opportunity, 'opportunity.unauthorized');
            $this->authorizeOrFail('delete', $opportunity, 'opportunity.cannot_delete_non_new');

            $this->service->delete($opportunity);

            return $this->successMessageResponse(__('opportunity.deleted_successfully'));
        } catch (OpportunityException $e) {
            throw $e;
        } catch (Throwable $throwable) {
            report($throwable);

            if ($throwable instanceof HttpExceptionInterface) {
                return $this->failedMessageResponse($throwable->getMessage(), $throwable->getStatusCode());
            }

            return $this->failedMessageResponse(__('something went wrong'));
        }
    }

    /**
     * Delete media from an opportunity.
     *
     * Only the author can delete media, and only when status is New.
     *
     * @authenticated
     *
     * @throws Throwable
     *
     * @urlParam opportunity string required The opportunity UUID.
     * @urlParam media string required The media UUID.
     *
     * @response 200 {
     *   "status": true,
     *   "message": "Media deleted successfully"
     * }
     * @response 403 {
     *   "status": false,
     *   "message": "You can only delete media when opportunity status is New"
     * }
     */
    public function deleteMedia(Opportunity $opportunity, Media $media): JsonResponse
    {
        try {
            $this->authorizeOrFail('update', $opportunity, 'opportunity.unauthorized');
            $this->authorizeOrFail('removeMedia', [$opportunity, $media], 'opportunity.unauthorized');
            $this->authorizeOrFail('deleteMedia', $opportunity, 'opportunity.cannot_delete_media_non_new');

            $this->service->deleteMedia($opportunity, $media);

            return $this->successMessageResponse(__('opportunity.media_deleted_successfully'));
        } catch (OpportunityException $e) {
            throw $e;
        } catch (Throwable $throwable) {
            report($throwable);

            if ($throwable instanceof HttpExceptionInterface) {
                return $this->failedMessageResponse($throwable->getMessage(), $throwable->getStatusCode());
            }

            return $this->failedMessageResponse(__('something went wrong'));
        }
    }
}
