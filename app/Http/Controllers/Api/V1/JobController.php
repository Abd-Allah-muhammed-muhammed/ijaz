<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\JobRequest;
use App\Http\Resources\Api\V1\JobCollection;
use App\Http\Resources\Api\V1\JobResource;
use App\Models\JobOffer;
use App\Traits\HasJobs;
use Carbon\Carbon;
use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use MMAE\ApiResponse\Traits\HasApiResponse;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Throwable;

class JobController extends Controller
{
    use HasApiResponse;

    public function all(Request $request): JsonResponse
    {
        return $this->successResponse(JobCollection::make(
            JobOffer::query()
                ->latest()
                ->when($request->filled('search'), function ($q) use ($request) {
                    $q->where('title', 'like', '%'.$request->string('search').'%')
                        ->orWhere('description', 'like', '%'.$request->string('search').'%');
                })
                ->with(['city.translation', 'region.translation', 'nationality.translation', 'skills.translation', 'user', 'media'])
                ->active()
                ->paginate($request->integer('per_page', 15))
        ));
    }

    public function index(Request $request): JsonResponse
    {
        /**
         * @var HasJobs $user
         */
        $user = auth()->user();

        return $this->successResponse(JobCollection::make(
            $user->jobs()
                ->latest()
                ->with(['city.translation', 'region.translation', 'nationality.translation', 'skills.translation'])
                ->active()
                ->paginate($request->integer('per_page', 15))
        ));
    }

    /**
     * @throws Throwable
     */
    public function store(JobRequest $request): JsonResponse
    {
        /**
         * @var HasJobs $user
         */
        $user = auth()->user();
        DB::beginTransaction();
        try {
            $data = $request->validated();
            /**
             * @var JobOffer $job
             */
            $job = $user->jobs()->create($data);
            if ($request->hasFile('files')) {
                $job->addMultipleMediaFromRequest(['files'])->each(function ($media) {
                    $media->toMediaCollection();
                });
            }
            //      $job->skills()->attach($data['skills']);
            $job->load(['city.translation', 'region.translation', 'nationality.translation', 'skills.translation', 'media']);
            DB::commit();

            return $this->successResponse(JobResource::make($job));
        } catch (Throwable $throwable) {
            DB::rollBack();
            report($throwable);

            return $this->failedMessageResponse(__('something went wrong'));
        }
    }

    /**
     * @throws Throwable
     */
    public function update(JobRequest $request, JobOffer $job): JsonResponse
    {
        /**
         * @var Model<HasJobs> $user
         */
        $user = auth()->user();
        if ($job->user()->isNot($user)) {
            return $this->failedMessageResponse(__('not_found'), 404);
        }
        DB::beginTransaction();
        try {
            $data = $request->validated();
            $job->update([
                ...$data,
                'expired_at' => Carbon::parse($data['expired_at'])->setTimezone('UTC'),
            ]);
            if ($request->hasFile('files')) {
                $job->addMultipleMediaFromRequest(['files'])->each(function ($media) {
                    $media->toMediaCollection();
                });
            }
            //      $job->skills()->sync($data['skills']);
            $job->load(['city.translation', 'region.translation', 'nationality.translation', 'skills.translation', 'media']);
            DB::commit();

            return $this->successResponse(JobResource::make($job));
        } catch (Throwable $throwable) {
            DB::rollBack();
            report($throwable);

            return $this->failedMessageResponse(__('something went wrong'));
        }

    }

    public function destroy(JobOffer $job): JsonResponse
    {
        /**
         * @var Model<HasJobs> $user
         */
        $user = auth()->user();
        if ($job->user()->isNot($user)) {
            return $this->failedMessageResponse(__('not_found'), 404);
        }
        try {
            $job->media->each->delete();
            $job->delete();

            return $this->successMessageResponse(__('job offer deleted successfully'));
        } catch (Throwable $throwable) {
            report($throwable);

            return $this->failedMessageResponse(__('something went wrong'));
        }
    }

    public function show(JobOffer $job): JsonResponse
    {
        return $this->successResponse(JobResource::make(
            $job->load([
                'city.translation', 'region.translation', 'nationality.translation', 'skills.translation', 'media',
            ])
        ));
    }

    public function deleteMedia(JobOffer $job, Media $media): JsonResponse
    {
        /**
         * @var Model<HasJobs> $user
         */
        $user = auth()->user();
        if ($job->user()->isNot($user)) {
            return $this->failedMessageResponse(__('not_found'), 404);
        }
        if ($media->model()->isNot($job)) {
            return $this->failedMessageResponse(__('media not found'), 404);
        }
        try {
            $media->delete();

            return $this->successMessageResponse(__('media deleted successfully'));
        } catch (Throwable $throwable) {
            report($throwable);

            return $this->failedMessageResponse(__('something went wrong'));
        }

    }
}
