<?php

namespace Modules\Classifieds\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use MMAE\ApiResponse\Traits\HasApiResponse;
use Modules\Classifieds\DTOs\InstituteAdvisementDTO;
use Modules\Classifieds\Http\Requests\Api\InstituteAdvisementRequest;
use Modules\Classifieds\Http\Resources\Api\InstituteAdvisementCollection;
use Modules\Classifieds\Http\Resources\Api\InstituteAdvisementResource;
use Modules\Classifieds\Models\InstituteAdvisement;
use Modules\Classifieds\QueryFilters\InstituteAdvisementFilters;
use Modules\Classifieds\Services\InstituteAdvisementService;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Throwable;

#[Group('Institute Advisements')]
class InstituteAdvisementController extends Controller
{
    use HasApiResponse;

    public function __construct(
        private readonly InstituteAdvisementService $service,
    ) {}

    /**
     * List own advisements (authenticated user's advisements)
     *
     * @queryParam status string optional Filter by status (when includeStatus is on).
     * @queryParam type string optional Filter by institute type (institute|university).
     * @queryParam study_type string optional Filter by study type (onsite|online|hybrid).
     * @queryParam specialization_id integer optional Filter by specialization.
     * @queryParam city_id integer optional Filter by city.
     * @queryParam region_id integer optional Filter by region.
     * @queryParam min_fees numeric optional Minimum fees.
     * @queryParam max_fees numeric optional Maximum fees.
     * @queryParam search string optional Search title or description.
     * @queryParam per_page integer optional Pagination size. Default: 15.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $filters = new InstituteAdvisementFilters($request, includeStatus: true);

        return $this->successResponse(
            InstituteAdvisementCollection::make(
                $this->service->listUserAdvisements($user, $filters)
            )
        );
    }

    /**
     * List all published advisements (public endpoint)
     *
     * @unauthenticated
     *
     * @queryParam type string optional Filter by institute type (institute|university).
     * @queryParam study_type string optional Filter by study type (onsite|online|hybrid).
     * @queryParam specialization_id integer optional Filter by specialization.
     * @queryParam city_id integer optional Filter by city.
     * @queryParam region_id integer optional Filter by region.
     * @queryParam min_fees numeric optional Minimum fees.
     * @queryParam max_fees numeric optional Maximum fees.
     * @queryParam search string optional Search title or description.
     * @queryParam per_page integer optional Pagination size. Default: 15.
     */
    public function all(Request $request): JsonResponse
    {
        $filters = new InstituteAdvisementFilters($request, includeStatus: false);

        return $this->successResponse(
            InstituteAdvisementCollection::make(
                $this->service->listPublishedAdvisements($filters)
            )
        );
    }

    /**
     * Create a new institute advisement
     *
     * @authenticated
     *
     * @throws Throwable
     */
    public function store(InstituteAdvisementRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        try {
            $dto = InstituteAdvisementDTO::fromRequest($request);
            $instituteAdvisement = $this->service->create($user, $dto);

            return $this->successResponse(InstituteAdvisementResource::make($instituteAdvisement));
        } catch (Throwable $throwable) {
            report($throwable);

            return $this->failedMessageResponse(__('something went wrong'));
        }
    }

    /**
     * Get an institute advisement
     *
     * @authenticated
     *
     * @throws Throwable
     */
    public function show(InstituteAdvisement $instituteAdvisement): JsonResponse
    {
        $instituteAdvisement = $this->service->loadForShow($instituteAdvisement);

        return $this->successResponse(InstituteAdvisementResource::make($instituteAdvisement));
    }

    /**
     * Update an existing institute advisement
     *
     * @authenticated
     *
     * @throws Throwable
     */
    public function update(InstituteAdvisementRequest $request, InstituteAdvisement $instituteAdvisement): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        try {
            $dto = InstituteAdvisementDTO::fromRequest($request);
            $instituteAdvisement = $this->service->update($user, $instituteAdvisement, $dto);

            return $this->successResponse(InstituteAdvisementResource::make($instituteAdvisement));
        } catch (AccessDeniedHttpException) {
            return $this->failedMessageResponse(__('forbidden !!'), Response::HTTP_FORBIDDEN);
        } catch (Throwable $throwable) {
            report($throwable);

            return $this->failedMessageResponse(__('something went wrong'));
        }
    }

    /**
     * Delete a media from an institute advisement
     *
     * @authenticated
     *
     * @throws Throwable
     */
    public function deleteMedia(InstituteAdvisement $instituteAdvisement, Media $media): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        try {
            $this->service->deleteMedia($user, $instituteAdvisement, $media);

            return $this->successMessageResponse(__('media deleted successfully'));
        } catch (AccessDeniedHttpException) {
            return $this->failedMessageResponse(__('forbidden !!'), Response::HTTP_FORBIDDEN);
        } catch (Throwable $throwable) {
            report($throwable);

            return $this->failedMessageResponse(__('something went wrong'));
        }
    }

    /**
     * Delete an institute advisement
     *
     * @authenticated
     *
     * @throws Throwable
     */
    public function destroy(InstituteAdvisement $instituteAdvisement): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        try {
            $this->service->delete($user, $instituteAdvisement);

            return $this->successMessageResponse(__('data deleted successfully'));
        } catch (AccessDeniedHttpException) {
            return $this->failedMessageResponse(__('forbidden !!'), Response::HTTP_FORBIDDEN);
        } catch (Throwable $throwable) {
            report($throwable);

            return $this->failedMessageResponse(__('something went wrong'));
        }
    }
}
