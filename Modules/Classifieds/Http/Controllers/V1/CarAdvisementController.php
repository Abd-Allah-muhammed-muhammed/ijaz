<?php

namespace Modules\Classifieds\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use MMAE\ApiResponse\Traits\HasApiResponse;
use Modules\Classifieds\DTOs\CarAdvisementDTO;
use Modules\Classifieds\Http\Requests\Api\CarAdvisementRequest;
use Modules\Classifieds\Http\Resources\Api\CarAdvisementCollection;
use Modules\Classifieds\Http\Resources\Api\CarAdvisementResource;
use Modules\Classifieds\Models\CarAdvisement;
use Modules\Classifieds\QueryFilters\CarAdvisementFilters;
use Modules\Classifieds\Services\CarAdvisementService;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Throwable;

#[Group('Car Advisements')]
class CarAdvisementController extends Controller
{
    use HasApiResponse;

    public function __construct(
        private readonly CarAdvisementService $carAdvisementService,
    ) {}

    /**
     * List own advisement's (authenticated user's advisement's)
     *
     * @queryParam operation string optional Filter by operation.
     * @queryParam usage_status string optional Filter by usage status.
     * @queryParam car_brand_id integer optional Filter by car brand.
     * @queryParam car_type_id integer optional Filter by car type.
     * @queryParam car_category_id integer optional Filter by car category.
     * @queryParam city_id integer optional Filter by city.
     * @queryParam region_id integer optional Filter by region.
     * @queryParam min_year integer optional Minimum year.
     * @queryParam max_year integer optional Maximum year.
     * @queryParam min_price numeric optional Minimum price.
     * @queryParam max_price numeric optional Maximum price.
     * @queryParam search string optional Search title or description.
     * @queryParam per_page integer optional Pagination size. Default: 15.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $filters = new CarAdvisementFilters($request);

        return $this->successResponse(
            CarAdvisementCollection::make(
                $this->carAdvisementService->listUserAdvisements($user, $filters)
            )
        );
    }

    /**
     * List all published advisements (public endpoint)
     *
     * @unauthenticated
     *
     * @queryParam operation string optional Filter by operation.
     * @queryParam usage_status string optional Filter by usage status.
     * @queryParam car_brand_id integer optional Filter by car brand.
     * @queryParam car_type_id integer optional Filter by car type.
     * @queryParam car_category_id integer optional Filter by car category.
     * @queryParam city_id integer optional Filter by city.
     * @queryParam region_id integer optional Filter by region.
     * @queryParam min_year integer optional Minimum year.
     * @queryParam max_year integer optional Maximum year.
     * @queryParam min_price numeric optional Minimum price.
     * @queryParam max_price numeric optional Maximum price.
     * @queryParam search string optional Search title or description.
     * @queryParam per_page integer optional Pagination size. Default: 15.
     */
    public function all(Request $request): JsonResponse
    {
        $filters = new CarAdvisementFilters($request);

        return $this->successResponse(
            CarAdvisementCollection::make(
                $this->carAdvisementService->listPublishedAdvisements($filters)
            )
        );
    }

    /**
     * @throws Throwable
     */
    public function store(CarAdvisementRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        try {
            $dto = CarAdvisementDTO::fromRequest($request);
            $carAdvisement = $this->carAdvisementService->create($user, $dto);

            return $this->successResponse(CarAdvisementResource::make($carAdvisement));
        } catch (Throwable $throwable) {
            report($throwable);

            return $this->failedMessageResponse(__('something went wrong'));
        }
    }

    public function show(CarAdvisement $carAdvisement): JsonResponse
    {
        $carAdvisement = $this->carAdvisementService->loadForShow($carAdvisement);

        return $this->successResponse(CarAdvisementResource::make($carAdvisement));
    }

    /**
     * @throws Throwable
     */
    public function update(CarAdvisementRequest $request, CarAdvisement $carAdvisement): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        try {
            $dto = CarAdvisementDTO::fromRequest($request);
            $carAdvisement = $this->carAdvisementService->update($user, $carAdvisement, $dto);

            return $this->successResponse(CarAdvisementResource::make($carAdvisement));
        } catch (AccessDeniedHttpException) {
            return $this->failedMessageResponse(__('forbidden !!'), Response::HTTP_FORBIDDEN);
        } catch (Throwable $throwable) {
            report($throwable);

            return $this->failedMessageResponse(__('something went wrong'));
        }
    }

    /**
     * @throws Throwable
     */
    public function deleteMedia(CarAdvisement $carAdvisement, Media $media): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        try {
            $this->carAdvisementService->deleteMedia($user, $carAdvisement, $media);

            return $this->successMessageResponse(__('media deleted successfully'));
        } catch (AccessDeniedHttpException) {
            return $this->failedMessageResponse(__('forbidden !!'), Response::HTTP_FORBIDDEN);
        } catch (Throwable $throwable) {
            report($throwable);

            return $this->failedMessageResponse(__('something went wrong'));
        }
    }

    /**
     * @throws Throwable
     */
    public function destroy(CarAdvisement $carAdvisement): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        try {
            $this->carAdvisementService->delete($user, $carAdvisement);

            return $this->successMessageResponse(__('data deleted successfully'));
        } catch (AccessDeniedHttpException) {
            return $this->failedMessageResponse(__('forbidden !!'), Response::HTTP_FORBIDDEN);
        } catch (Throwable $throwable) {
            report($throwable);

            return $this->failedMessageResponse(__('something went wrong'));
        }
    }
}
