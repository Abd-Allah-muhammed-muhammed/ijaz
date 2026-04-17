<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\Advisements\AdvisementStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CarAdvisementRequest;
use App\Http\Resources\Api\V1\CarAdvisementCollection;
use App\Http\Resources\Api\V1\CarAdvisementResource;
use App\Models\CarAdvisement;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use MMAE\ApiResponse\Traits\HasApiResponse;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

#[Group('Car Advisements')]
class CarAdvisementController extends Controller
{
    use HasApiResponse;

    /**
     * List own advisement's (authenticated user's advisement's)
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();

        return $this->successResponse(
            CarAdvisementCollection::make(
                $user->carAdvisements()
                    ->when($request->filled('status'), function (Builder $query) use ($request) {
                        $query->where('status', $request->string('status'));
                    })
                    ->when($request->filled('operation'), function (Builder $query) use ($request) {
                        $query->where('operation', $request->string('operation'));
                    })
                    ->when($request->filled('usage_status'), function (Builder $query) use ($request) {
                        $query->where('usage_status', $request->string('usage_status'));
                    })
                    ->when($request->filled('car_brand_id'), function (Builder $query) use ($request) {
                        $query->where('car_brand_id', $request->integer('car_brand_id'));
                    })
                    ->when($request->filled('car_type_id'), function (Builder $query) use ($request) {
                        $query->where('car_type_id', $request->integer('car_type_id'));
                    })
                    ->when($request->filled('car_category_id'), function (Builder $query) use ($request) {
                        $query->where('car_category_id', $request->integer('car_category_id'));
                    })
                    ->when($request->filled('city_id'), function (Builder $query) use ($request) {
                        $query->where('city_id', $request->integer('city_id'));
                    })
                    ->when($request->filled('region_id'), function (Builder $query) use ($request) {
                        $query->where('region_id', $request->integer('region_id'));
                    })
                    ->when($request->filled('min_year'), function (Builder $query) use ($request) {
                        $query->where('year', '>=', $request->integer('min_year'));
                    })
                    ->when($request->filled('max_year'), function (Builder $query) use ($request) {
                        $query->where('year', '<=', $request->integer('max_year'));
                    })
                    ->when($request->filled('min_price'), function (Builder $query) use ($request) {
                        $query->where('price', '>=', $request->float('min_price'));
                    })
                    ->when($request->filled('max_price'), function (Builder $query) use ($request) {
                        $query->where('price', '<=', $request->float('max_price'));
                    })
                    ->when($request->filled('search'), function (Builder $query) use ($request) {
                        $search = $request->string('search');
                        $query->where(function (Builder $q) use ($search) {
                            $q->where('title', 'like', "%{$search}%")
                                ->orWhere('description', 'like', "%{$search}%");
                        });
                    })
                    ->with([
                        'carBrand',
                        'carType',
                        'carCategory',
                        'city',
                        'region',
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
            CarAdvisementCollection::make(
                CarAdvisement::query()
                    ->published()
                    ->when($request->filled('operation'), function (Builder $query) use ($request) {
                        $query->where('operation', $request->string('operation'));
                    })
                    ->when($request->filled('usage_status'), function (Builder $query) use ($request) {
                        $query->where('usage_status', $request->string('usage_status'));
                    })
                    ->when($request->filled('car_brand_id'), function (Builder $query) use ($request) {
                        $query->where('car_brand_id', $request->integer('car_brand_id'));
                    })
                    ->when($request->filled('car_type_id'), function (Builder $query) use ($request) {
                        $query->where('car_type_id', $request->integer('car_type_id'));
                    })
                    ->when($request->filled('car_category_id'), function (Builder $query) use ($request) {
                        $query->where('car_category_id', $request->integer('car_category_id'));
                    })
                    ->when($request->filled('city_id'), function (Builder $query) use ($request) {
                        $query->where('city_id', $request->integer('city_id'));
                    })
                    ->when($request->filled('region_id'), function (Builder $query) use ($request) {
                        $query->where('region_id', $request->integer('region_id'));
                    })
                    ->when($request->filled('min_year'), function (Builder $query) use ($request) {
                        $query->where('year', '>=', $request->integer('min_year'));
                    })
                    ->when($request->filled('max_year'), function (Builder $query) use ($request) {
                        $query->where('year', '<=', $request->integer('max_year'));
                    })
                    ->when($request->filled('min_price'), function (Builder $query) use ($request) {
                        $query->where('price', '>=', $request->float('min_price'));
                    })
                    ->when($request->filled('max_price'), function (Builder $query) use ($request) {
                        $query->where('price', '<=', $request->float('max_price'));
                    })
                    ->when($request->filled('search'), function (Builder $query) use ($request) {
                        $search = $request->string('search');
                        $query->where(function (Builder $q) use ($search) {
                            $q->where('title', 'like', "%{$search}%")
                                ->orWhere('description', 'like', "%{$search}%");
                        });
                    })
                    ->with([
                        'carBrand',
                        'carType',
                        'carCategory',
                        'city',
                        'region',
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
    public function store(CarAdvisementRequest $request): JsonResponse
    {
        $user = auth()->user();
        DB::beginTransaction();
        try {
            $data = $request->validated();

            /** @var CarAdvisement $carAdvisement */
            $carAdvisement = $user->carAdvisements()->create([
                ...$data,
                'status' => AdvisementStatusEnum::PENDING,
            ]);

            if ($request->hasFile('files')) {
                $carAdvisement->addMultipleMediaFromRequest(['files'])->each(function ($media) {
                    $media->toMediaCollection();
                });
            }

            $carAdvisement->load([
                'carBrand',
                'carType',
                'carCategory',
                'city',
                'region',
                'media',
            ]);

            DB::commit();

            return $this->successResponse(CarAdvisementResource::make($carAdvisement));
        } catch (Throwable $throwable) {
            DB::rollBack();
            report($throwable);

            return $this->failedMessageResponse(__('something went wrong'));
        }
    }

    public function show(CarAdvisement $carAdvisement): JsonResponse
    {
        $carAdvisement->load([
            'carBrand',
            'carType',
            'carCategory',
            'city',
            'region',
            'user',
            'media',
        ]);

        return $this->successResponse(CarAdvisementResource::make($carAdvisement));
    }

    /**
     * @throws Throwable
     */
    public function edit(CarAdvisementRequest $request, CarAdvisement $carAdvisement): JsonResponse
    {
        $user = auth()->user();

        if ($carAdvisement->user()->isNot($user)) {
            return $this->failedMessageResponse(__('forbidden !!'), Response::HTTP_FORBIDDEN);
        }

        DB::beginTransaction();
        try {
            $data = $request->validated();

            $carAdvisement->update($data);

            if ($request->hasFile('files')) {
                $carAdvisement->addMultipleMediaFromRequest(['files'])->each(function ($media) {
                    $media->toMediaCollection();
                });
            }

            $carAdvisement->load([
                'carBrand',
                'carType',
                'carCategory',
                'city',
                'region',
                'media',
            ]);

            DB::commit();

            return $this->successResponse(CarAdvisementResource::make($carAdvisement));
        } catch (Throwable $throwable) {
            DB::rollBack();
            report($throwable);

            return $this->failedMessageResponse(__('something went wrong'));
        }
    }

    /**
     * @throws Throwable
     */
    public function deleteMedia(CarAdvisement $carAdvisement, Media $media): JsonResponse
    {
        $user = auth()->user();

        if ($carAdvisement->user()->isNot($user)) {
            return $this->failedMessageResponse(__('forbidden !!'), Response::HTTP_FORBIDDEN);
        }

        if ($media->model()->isNot($carAdvisement)) {
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
    public function destroy(CarAdvisement $carAdvisement): JsonResponse
    {
        $user = auth()->user();

        if ($carAdvisement->user()->isNot($user)) {
            return $this->failedMessageResponse(__('forbidden !!'), Response::HTTP_FORBIDDEN);
        }

        DB::beginTransaction();
        try {
            $carAdvisement->media->each->delete();
            $carAdvisement->delete();
            DB::commit();

            return $this->successMessageResponse(__('data deleted successfully'));
        } catch (Throwable $throwable) {
            DB::rollBack();
            report($throwable);

            return $this->failedMessageResponse(__('something went wrong'));
        }
    }
}
