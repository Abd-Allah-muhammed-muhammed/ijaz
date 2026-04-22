<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\PropertyAdvisement\PropertyAdvisementDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\PropertyAdvisementRequest;
use App\Http\Resources\Api\V1\PropertyAdvisementCollection;
use App\Http\Resources\Api\V1\PropertyAdvisementResource;
use App\Models\PropertyAdvisement;
use App\Models\User;
use App\QueryFilters\PropertyAdvisement\PropertyAdvisementFilters;
use App\Services\PropertyAdvisement\PropertyAdvisementService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use MMAE\ApiResponse\Traits\HasApiResponse;
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

  public function show(PropertyAdvisement $propertyAdvisement): JsonResponse
  {
    $propertyAdvisement = $this->propertyAdvisementService->loadForShow($propertyAdvisement);

    return $this->successResponse(PropertyAdvisementResource::make($propertyAdvisement));
  }

  /**
   * @throws Throwable
   */
  public function edit(PropertyAdvisementRequest $request, PropertyAdvisement $propertyAdvisement): JsonResponse
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
