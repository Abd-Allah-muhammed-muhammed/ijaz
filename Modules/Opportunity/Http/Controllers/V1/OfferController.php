<?php

namespace Modules\Opportunity\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use MMAE\ApiResponse\Traits\HasApiResponse;
use Modules\Opportunity\Actions\Offer\AcceptOfferAction;
use Modules\Opportunity\Actions\Offer\RejectOfferAction;
use Modules\Opportunity\Actions\Offer\SubmitOfferAction;
use Modules\Opportunity\Contracts\Repositories\OpportunityOfferRepositoryInterface;
use Modules\Opportunity\DTOs\OfferData;
use Modules\Opportunity\Exceptions\OpportunityException;
use Modules\Opportunity\Http\Controllers\Concerns\AuthorizesOpportunityRequests;
use Modules\Opportunity\Http\Requests\StoreOfferRequest;
use Modules\Opportunity\Http\Resources\OfferCollection;
use Modules\Opportunity\Http\Resources\OfferResource;
use Modules\Opportunity\Http\Resources\OpportunityResource;
use Modules\Opportunity\Models\Opportunity;
use Modules\Opportunity\Models\OpportunityOffer;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

#[Group('Opportunity Offers')]
class OfferController extends Controller
{
    use AuthorizesOpportunityRequests;
    use HasApiResponse;

    public function __construct(
        private readonly OpportunityOfferRepositoryInterface $offers,
        private readonly SubmitOfferAction $submitOfferAction,
        private readonly AcceptOfferAction $acceptOfferAction,
        private readonly RejectOfferAction $rejectOfferAction,
    ) {}

    /**
     * List offers for an opportunity.
     *
     * @authenticated
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
     *         "price": 2500.00,
     *         "description": "I can deliver in 2 weeks",
     *         "status": { "value": "pending", "label": "Pending", "color": "primary" },
     *         "author": { "id": 2, "name": "Sara Ali" },
     *         "created_at": "2026-06-02T10:00:00+00:00"
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
            OfferCollection::make(
                $this->offers->listByOpportunity($opportunity, auth()->user(), $request->integer('per_page', 10))
            )
        );
    }

    /**
     * Submit an offer on an opportunity.
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
     *     "price": 2500.00,
     *     "description": "I can deliver in 2 weeks",
     *     "status": { "value": "pending", "label": "Pending", "color": "primary" },
     *     "author": { "id": 2, "name": "Sara Ali" },
     *     "created_at": "2026-06-02T10:00:00+00:00"
     *   }
     * }
     * @response 422 {
     *   "status": false,
     *   "message": "Offers can only be submitted on open opportunities"
     * }
     */
    public function store(StoreOfferRequest $request, Opportunity $opportunity): JsonResponse
    {
        try {
            $user = auth()->user();

            if (
                $opportunity->author_type === $user::class
                && $opportunity->author_id === $user->getKey()
            ) {
                throw new HttpException(403, __('opportunity.cannot_submit_offer_on_own_opportunity'));
            }

            $this->authorizeOrFail('create', [OpportunityOffer::class, $opportunity], 'opportunity.cannot_submit_offer_non_new', 422);

            $data = OfferData::fromRequest($request);
            $offer = $this->submitOfferAction->handle($opportunity, $data, auth()->user());

            return $this->successResponse(OfferResource::make($offer));
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
     * Accept an offer on an opportunity.
     *
     * Only the opportunity author can accept. Rejects other pending offers and opens a chat conversation.
     *
     * @authenticated
     *
     * @throws Throwable
     *
     * @urlParam opportunity string required The opportunity UUID.
     * @urlParam offer string required The offer UUID.
     *
     * @response 200 {
     *   "status": true,
     *   "data": {
     *     "id": "01234567-89ab-cdef-0123-456789abcdef",
     *     "status": { "value": "offer_accepted", "label": "Offer Accepted", "color": "info" },
     *     "accepted_offer": {
     *       "id": "01234567-89ab-cdef-0123-456789abcd00",
     *       "price": 2500.00,
     *       "status": { "value": "accepted", "label": "Accepted", "color": "success" }
     *     }
     *   }
     * }
     * @response 403 {
     *   "status": false,
     *   "message": "You are not authorized to perform this action"
     * }
     * @response 422 {
     *   "status": false,
     *   "message": "You cannot accept an offer for this opportunity"
     * }
     */
    public function accept(Opportunity $opportunity, OpportunityOffer $offer): JsonResponse
    {
        try {
            $this->authorizeOrFail('update', $opportunity, 'opportunity.unauthorized');
            $this->authorizeOrFail('acceptOffer', [$opportunity, $offer], 'opportunity.cannot_accept_offer', 422);

            $opportunity = $this->acceptOfferAction->handle($opportunity, $offer);

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
     * Reject an offer on an opportunity.
     *
     * Only the opportunity author can reject an offer.
     *
     * @authenticated
     *
     * @throws Throwable
     *
     * @urlParam opportunity string required The opportunity UUID.
     * @urlParam offer string required The offer UUID.
     *
     * @response 200 {
     *   "status": true,
     *   "message": "Offer rejected successfully"
     * }
     * @response 403 {
     *   "status": false,
     *   "message": "This offer does not belong to the specified opportunity"
     * }
     */
    public function reject(Opportunity $opportunity, OpportunityOffer $offer): JsonResponse
    {
        try {
            $this->authorizeOrFail('update', $opportunity, 'opportunity.unauthorized');
            $this->authorizeOrFail('rejectOffer', [$opportunity, $offer], 'opportunity.offer_not_belong_to_opportunity');

            $this->rejectOfferAction->handle($opportunity, $offer);

            return $this->successMessageResponse(__('opportunity.offer_rejected_successfully'));
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
