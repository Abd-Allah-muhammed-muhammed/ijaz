<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\Advisements\AdvisementStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\PropertyAdvisementRequest;
use App\Http\Resources\Api\V1\PropertyAdvisementCollection;
use App\Http\Resources\Api\V1\PropertyAdvisementResource;
use App\Models\PropertyAdvisement;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use MMAE\ApiResponse\Traits\HasApiResponse;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

#[Group('Property Advisements')]
class PropertyAdvisementController extends Controller
{
    use HasApiResponse;

    /**
     * List own advisement's (authenticated user's advisement's)
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();

        return $this->successResponse(
            PropertyAdvisementCollection::make(
                $user->propertyAdvisements()
                    ->when($request->filled('status'), function (Builder $query) use ($request) {
                        $query->where('status', $request->string('status'));
                    })
                    ->when($request->filled('operation'), function (Builder $query) use ($request) {
                        $query->where('operation', $request->string('operation'));
                    })
                    ->when($request->filled('property_type_id'), function (Builder $query) use ($request) {
                        $query->where('property_type_id', $request->integer('property_type_id'));
                    })
                    ->when($request->filled('city_id'), function (Builder $query) use ($request) {
                        $query->where('city_id', $request->integer('city_id'));
                    })
                    ->when($request->filled('region_id'), function (Builder $query) use ($request) {
                        $query->where('region_id', $request->integer('region_id'));
                    })
                    ->when($request->filled('category_id'), function (Builder $query) use ($request) {
                        $query->where('category_id', $request->integer('category_id'));
                    })
                    ->when($request->filled('min_price'), function (Builder $query) use ($request) {
                        $query->where('price', '>=', $request->float('min_price'));
                    })
                    ->when($request->filled('max_price'), function (Builder $query) use ($request) {
                        $query->where('price', '<=', $request->float('max_price'));
                    })
                    ->when($request->filled('min_area'), function (Builder $query) use ($request) {
                        $query->where('area', '>=', $request->float('min_area'));
                    })
                    ->when($request->filled('max_area'), function (Builder $query) use ($request) {
                        $query->where('area', '<=', $request->float('max_area'));
                    })
                    ->when($request->filled('bedrooms_count'), function (Builder $query) use ($request) {
                        $query->where('bedrooms_count', $request->integer('bedrooms_count'));
                    })
                    ->when($request->filled('search'), function (Builder $query) use ($request) {
                        $search = $request->string('search');
                        $query->where(function (Builder $q) use ($search) {
                            $q->where('title', 'like', "%{$search}%")
                                ->orWhere('description', 'like', "%{$search}%");
                        });
                    })
                    ->with([
                        'propertyType.translation',
                        'city.translation',
                        'region.translation',
                        'category.translation',
                        'media',
                    ])
                    ->latest()
                    ->paginate($request->integer('per_page', 15))
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
        return $this->successResponse(
            PropertyAdvisementCollection::make(
                PropertyAdvisement::query()
                    ->published()
                    ->when($request->filled('operation'), function (Builder $query) use ($request) {
                        $query->where('operation', $request->string('operation'));
                    })
                    ->when($request->filled('property_type_id'), function (Builder $query) use ($request) {
                        $query->where('property_type_id', $request->integer('property_type_id'));
                    })
                    ->when($request->filled('city_id'), function (Builder $query) use ($request) {
                        $query->where('city_id', $request->integer('city_id'));
                    })
                    ->when($request->filled('region_id'), function (Builder $query) use ($request) {
                        $query->where('region_id', $request->integer('region_id'));
                    })
                    ->when($request->filled('category_id'), function (Builder $query) use ($request) {
                        $query->where('category_id', $request->integer('category_id'));
                    })
                    ->when($request->filled('min_price'), function (Builder $query) use ($request) {
                        $query->where('price', '>=', $request->float('min_price'));
                    })
                    ->when($request->filled('max_price'), function (Builder $query) use ($request) {
                        $query->where('price', '<=', $request->float('max_price'));
                    })
                    ->when($request->filled('min_area'), function (Builder $query) use ($request) {
                        $query->where('area', '>=', $request->float('min_area'));
                    })
                    ->when($request->filled('max_area'), function (Builder $query) use ($request) {
                        $query->where('area', '<=', $request->float('max_area'));
                    })
                    ->when($request->filled('bedrooms_count'), function (Builder $query) use ($request) {
                        $query->where('bedrooms_count', $request->integer('bedrooms_count'));
                    })
                    ->when($request->filled('search'), function (Builder $query) use ($request) {
                        $search = $request->string('search');
                        $query->where(function (Builder $q) use ($search) {
                            $q->where('title', 'like', "%{$search}%")
                                ->orWhere('description', 'like', "%{$search}%");
                        });
                    })
                    ->with([
                        'propertyType.translation',
                        'city.translation',
                        'region.translation',
                        'category.translation',
                        'user',
                        'media',
                    ])
                    ->latest()
                    ->paginate($request->integer('per_page', 15))
            )
        );
    }

    /**
     * @throws Throwable
     */
    public function store(PropertyAdvisementRequest $request): JsonResponse
    {
        $user = auth()->user();
        DB::beginTransaction();
        try {
            $data = $request->validated();

            /** @var PropertyAdvisement $propertyAdvisement */
            $propertyAdvisement = $user->propertyAdvisements()->create([
                ...$data,
                'status' => AdvisementStatusEnum::PENDING,
            ]);

            if ($request->hasFile('files')) {
                $propertyAdvisement->addMultipleMediaFromRequest(['files'])->each(function ($media) {
                    $media->toMediaCollection();
                });
            }

            $propertyAdvisement->load([
                'propertyType.translation',
                'city.translation',
                'region.translation',
                'category.translation',
                'media',
            ]);

            DB::commit();

            return $this->successResponse(PropertyAdvisementResource::make($propertyAdvisement));
        } catch (Throwable $throwable) {
            DB::rollBack();
            report($throwable);

            return $this->failedMessageResponse(__('something went wrong'));
        }
    }

    public function show(PropertyAdvisement $propertyAdvisement): JsonResponse
    {
        $propertyAdvisement->load([
            'propertyType.translation',
            'city.translation',
            'region.translation',
            'category.translation',
            'user',
            'media',
        ]);

        return $this->successResponse(PropertyAdvisementResource::make($propertyAdvisement));
    }

    /**
     * @throws Throwable
     */
    public function edit(PropertyAdvisementRequest $request, PropertyAdvisement $propertyAdvisement): JsonResponse
    {
        $user = auth()->user();

        if ($propertyAdvisement->user()->isNot($user)) {
            return $this->failedMessageResponse(__('forbidden !!'), Response::HTTP_FORBIDDEN);
        }

        DB::beginTransaction();
        try {
            $data = $request->validated();

            $propertyAdvisement->update($data);

            if ($request->hasFile('files')) {
                $propertyAdvisement->addMultipleMediaFromRequest(['files'])->each(function ($media) {
                    $media->toMediaCollection();
                });
            }

            $propertyAdvisement->load([
                'propertyType.translation',
                'city.translation',
                'region.translation',
                'category.translation',
                'media',
            ]);

            DB::commit();

            return $this->successResponse(PropertyAdvisementResource::make($propertyAdvisement));
        } catch (Throwable $throwable) {
            DB::rollBack();
            report($throwable);

            return $this->failedMessageResponse(__('something went wrong'));
        }
    }

    /**
     * @throws Throwable
     */
    public function deleteMedia(PropertyAdvisement $propertyAdvisement, Media $media): JsonResponse
    {
        $user = auth()->user();

        if ($propertyAdvisement->user()->isNot($user)) {
            return $this->failedMessageResponse(__('forbidden !!'), Response::HTTP_FORBIDDEN);
        }

        if ($media->model()->isNot($propertyAdvisement)) {
            return $this->failedMessageResponse(__('forbidden !!'), Response::HTTP_FORBIDDEN);
        }

        DB::beginTransaction();
        try {
            $media->delete();
            DB::commit();

            return $this->successMessageResponse(__('media deleted successfully'));
        } catch (Throwable $throwable) {
            DB::rollBack();
            report($throwable);

            return $this->failedMessageResponse(__('something went wrong'));
        }
    }

    /**
     * @throws Throwable
     */
    public function destroy(PropertyAdvisement $propertyAdvisement): JsonResponse
    {
        $user = auth()->user();

        if ($propertyAdvisement->user()->isNot($user)) {
            return $this->failedMessageResponse(__('forbidden !!'), Response::HTTP_FORBIDDEN);
        }

        DB::beginTransaction();
        try {
            $propertyAdvisement->media->each->delete();
            $propertyAdvisement->delete();
            DB::commit();

            return $this->successMessageResponse(__('data deleted successfully'));
        } catch (Throwable $throwable) {
            DB::rollBack();
            report($throwable);

            return $this->failedMessageResponse(__('something went wrong'));
        }
    }
}
