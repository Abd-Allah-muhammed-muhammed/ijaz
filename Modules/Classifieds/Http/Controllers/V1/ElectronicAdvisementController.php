<?php

namespace Modules\Classifieds\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use MMAE\ApiResponse\Traits\HasApiResponse;
use Modules\Classifieds\DTOs\ElectronicAdvisementDTO;
use Modules\Classifieds\Http\Requests\Api\ElectronicAdvisementRequest;
use Modules\Classifieds\Http\Resources\Api\ElectronicAdvisementCollection;
use Modules\Classifieds\Http\Resources\Api\ElectronicAdvisementResource;
use Modules\Classifieds\Models\ElectronicAdvisement;
use Modules\Classifieds\QueryFilters\ElectronicAdvisementFilters;
use Modules\Classifieds\Services\ElectronicAdvisementService;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Throwable;

#[Group('Electronic Advisements')]
class ElectronicAdvisementController extends Controller
{
    use HasApiResponse;

    public function __construct(
        private readonly ElectronicAdvisementService $service,
    ) {}

    /**
     * List own advisements (authenticated user's advisements)
     *
     * @queryParam status string optional Filter by status (when includeStatus is on).
     * @queryParam condition string optional Filter by condition (new|used|less_than_year).
     * @queryParam device_category_id integer optional Filter by device category.
     * @queryParam city_id integer optional Filter by city.
     * @queryParam region_id integer optional Filter by region.
     * @queryParam min_price numeric optional Minimum price.
     * @queryParam max_price numeric optional Maximum price.
     * @queryParam search string optional Search title or description.
     * @queryParam per_page integer optional Pagination size. Default: 15.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $filters = new ElectronicAdvisementFilters($request, includeStatus: true);

        return $this->successResponse(
            ElectronicAdvisementCollection::make(
                $this->service->listUserAdvisements($user, $filters)
            )
        );
    }

    /**
     * List all published advisements (public endpoint)
     *
     * @unauthenticated
     *
     * @queryParam condition string optional Filter by condition (new|used|less_than_year).
     * @queryParam device_category_id integer optional Filter by device category.
     * @queryParam city_id integer optional Filter by city.
     * @queryParam region_id integer optional Filter by region.
     * @queryParam min_price numeric optional Minimum price.
     * @queryParam max_price numeric optional Maximum price.
     * @queryParam search string optional Search title or description.
     * @queryParam per_page integer optional Pagination size. Default: 15.
     */
    public function all(Request $request): JsonResponse
    {
        $filters = new ElectronicAdvisementFilters($request, includeStatus: false);

        return $this->successResponse(
            ElectronicAdvisementCollection::make(
                $this->service->listPublishedAdvisements($filters)
            )
        );
    }

    /**
     * Create a new electronic advisement
     *
     * @authenticated
     * @throws Throwable
     */
    public function store(ElectronicAdvisementRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        try {
            $dto = ElectronicAdvisementDTO::fromRequest($request);
            $electronicAdvisement = $this->service->create($user, $dto);

            return $this->successResponse(ElectronicAdvisementResource::make($electronicAdvisement));
        } catch (Throwable $throwable) {
            report($throwable);

            return $this->failedMessageResponse(__('something went wrong'));
        }
    }

    /**
     * Get an electronic advisement
     *
     * @authenticated
     * @throws Throwable
     */
    public function show(ElectronicAdvisement $electronicAdvisement): JsonResponse
    {
        $electronicAdvisement = $this->service->loadForShow($electronicAdvisement);

        return $this->successResponse(ElectronicAdvisementResource::make($electronicAdvisement));
    }

    /**
     * Update an existing electronic advisement
     *
     * @authenticated
     * @throws Throwable
     */
    public function update(ElectronicAdvisementRequest $request, ElectronicAdvisement $electronicAdvisement): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        try {
            $dto = ElectronicAdvisementDTO::fromRequest($request);
            $electronicAdvisement = $this->service->update($user, $electronicAdvisement, $dto);

            return $this->successResponse(ElectronicAdvisementResource::make($electronicAdvisement));
        } catch (AccessDeniedHttpException) {
            return $this->failedMessageResponse(__('forbidden !!'), Response::HTTP_FORBIDDEN);
        } catch (Throwable $throwable) {
            report($throwable);

            return $this->failedMessageResponse(__('something went wrong'));
        }
    }

    /**
     * Delete a media from an electronic advisement
     *
     * @authenticated
     * @throws Throwable
     */
    public function deleteMedia(ElectronicAdvisement $electronicAdvisement, Media $media): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        try {
            $this->service->deleteMedia($user, $electronicAdvisement, $media);

            return $this->successMessageResponse(__('media deleted successfully'));
        } catch (AccessDeniedHttpException) {
            return $this->failedMessageResponse(__('forbidden !!'), Response::HTTP_FORBIDDEN);
        } catch (Throwable $throwable) {
            report($throwable);

            return $this->failedMessageResponse(__('something went wrong'));
        }
    }

    /**
     * Delete an electronic advisement
     *
     * @authenticated
     * @throws Throwable
     */
    public function destroy(ElectronicAdvisement $electronicAdvisement): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        try {
            $this->service->delete($user, $electronicAdvisement);

            return $this->successMessageResponse(__('data deleted successfully'));
        } catch (AccessDeniedHttpException) {
            return $this->failedMessageResponse(__('forbidden !!'), Response::HTTP_FORBIDDEN);
        } catch (Throwable $throwable) {
            report($throwable);

            return $this->failedMessageResponse(__('something went wrong'));
        }
    }
}
