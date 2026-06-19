<?php

namespace Modules\Classifieds\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use MMAE\ApiResponse\Traits\HasApiResponse;
use Modules\Classifieds\DTOs\PropertyAdvisementDTO;
use Modules\Classifieds\Http\Requests\Api\PropertyAdvisementRequest;
use Modules\Classifieds\Http\Resources\Api\PropertyAdvisementCollection;
use Modules\Classifieds\Http\Resources\Api\PropertyAdvisementResource;
use Modules\Classifieds\Models\PropertyAdvisement;
use Modules\Classifieds\QueryFilters\PropertyAdvisementFilters;
use Modules\Classifieds\Services\PropertyAdvisementService;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Throwable;

#[Group('Property Advisements')]
class PropertyAdvisementController extends Controller
{
    use HasApiResponse;

    public function __construct(
        private readonly PropertyAdvisementService $propertyAdvisementService,
    ) {}

    /**
     * List own advisement's (authenticated user's advisement's)
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $filters = new PropertyAdvisementFilters($request, includeStatus: true);

        return $this->successResponse(
            PropertyAdvisementCollection::make(
                $this->propertyAdvisementService->listUserAdvisements($user, $filters)
            )
        );
    }

    /**
     * List all published advisements (public endpoint)
     *
     * @unauthenticated
     */
    public function all(Request $request): JsonResponse
    {
        $filters = new PropertyAdvisementFilters($request);

        return $this->successResponse(
            PropertyAdvisementCollection::make(
                $this->propertyAdvisementService->listPublishedAdvisements($filters)
            )
        );
    }

    /**
     * Create a new property advisement
     *
     * @authenticated
     *
     * @throws Throwable
     */
    public function store(PropertyAdvisementRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        try {
            $dto = PropertyAdvisementDTO::fromRequest($request);
            $propertyAdvisement = $this->propertyAdvisementService->create($user, $dto);

            return $this->successResponse(PropertyAdvisementResource::make($propertyAdvisement));
        } catch (Throwable $throwable) {
            report($throwable);

            return $this->failedMessageResponse(__('something went wrong'));
        }
    }

    /**
     * Get a property advisement
     *
     * @authenticated
     *
     * @throws Throwable
     */
    public function show(PropertyAdvisement $propertyAdvisement): JsonResponse
    {
        $propertyAdvisement = $this->propertyAdvisementService->loadForShow($propertyAdvisement);

        return $this->successResponse(PropertyAdvisementResource::make($propertyAdvisement));
    }

    /**
     * Update an existing property advisement
     *
     * @authenticated
     *
     * @throws Throwable
     */
    public function update(PropertyAdvisementRequest $request, PropertyAdvisement $propertyAdvisement): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        try {
            $dto = PropertyAdvisementDTO::fromRequest($request);
            $propertyAdvisement = $this->propertyAdvisementService->update($user, $propertyAdvisement, $dto);

            return $this->successResponse(PropertyAdvisementResource::make($propertyAdvisement));
        } catch (AccessDeniedHttpException) {
            return $this->failedMessageResponse(__('forbidden !!'), Response::HTTP_FORBIDDEN);
        } catch (Throwable $throwable) {
            report($throwable);

            return $this->failedMessageResponse(__('something went wrong'));
        }
    }

    /**
     * Delete a media from a property advisement
     *
     * @authenticated
     *
     * @throws Throwable
     */
    public function deleteMedia(PropertyAdvisement $propertyAdvisement, Media $media): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        try {
            $this->propertyAdvisementService->deleteMedia($user, $propertyAdvisement, $media);

            return $this->successMessageResponse(__('media deleted successfully'));
        } catch (AccessDeniedHttpException) {
            return $this->failedMessageResponse(__('forbidden !!'), Response::HTTP_FORBIDDEN);
        } catch (Throwable $throwable) {
            report($throwable);

            return $this->failedMessageResponse(__('something went wrong'));
        }
    }

    /**
     * Delete a property advisement
     *
     * @authenticated
     *
     * @throws Throwable
     */
    public function destroy(PropertyAdvisement $propertyAdvisement): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        try {
            $this->propertyAdvisementService->delete($user, $propertyAdvisement);

            return $this->successMessageResponse(__('data deleted successfully'));
        } catch (AccessDeniedHttpException) {
            return $this->failedMessageResponse(__('forbidden !!'), Response::HTTP_FORBIDDEN);
        } catch (Throwable $throwable) {
            report($throwable);

            return $this->failedMessageResponse(__('something went wrong'));
        }
    }
}
