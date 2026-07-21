<?php

namespace Modules\Jobs\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use MMAE\ApiResponse\Traits\HasApiResponse;
use Modules\Jobs\Concerns\HasJobs;
use Modules\Jobs\DTOs\StoreJobDTO;
use Modules\Jobs\DTOs\UpdateJobDTO;
use Modules\Jobs\Exceptions\JobsException;
use Modules\Jobs\Http\Requests\JobRequest;
use Modules\Jobs\Http\Resources\JobCollection;
use Modules\Jobs\Http\Resources\JobResource;
use Modules\Jobs\Models\JobOffer;
use Modules\Jobs\Services\JobService;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Throwable;

#[Group('Jobs')]
class JobController extends Controller
{
    use HasApiResponse;

    public function __construct(
        private readonly JobService $service,
    ) {}

    public function all(Request $request): JsonResponse
    {
        return $this->successResponse(JobCollection::make(
            $this->service->listPublic([
                'search' => $request->filled('search') ? $request->string('search')->toString() : null,
                'per_page' => $request->integer('per_page', 15),
            ])
        ));
    }

    public function index(Request $request): JsonResponse
    {
        /** @var HasJobs $user */
        $user = auth()->user();

        return $this->successResponse(JobCollection::make(
            $this->service->listByActor($user, [
                'per_page' => $request->integer('per_page', 15),
            ])
        ));
    }

    /**
     * @throws Throwable
     */
    public function store(JobRequest $request): JsonResponse
    {
        /** @var HasJobs $user */
        $user = auth()->user();

        try {
            $job = $this->service->create(
                $user,
                StoreJobDTO::fromValidated($request->validated()),
                $request->file('files'),
            );

            return $this->successResponse(JobResource::make($job));
        } catch (Throwable $throwable) {
            report($throwable);

            return $this->failedMessageResponse(__('something went wrong'));
        }
    }

    /**
     * @throws Throwable
     */
    public function update(JobRequest $request, JobOffer $job): JsonResponse
    {
        /** @var HasJobs $user */
        $user = auth()->user();

        try {
            $job = $this->service->update(
                $job,
                $user,
                UpdateJobDTO::fromValidated($request->validated()),
                $request->file('files'),
            );

            return $this->successResponse(JobResource::make($job));
        } catch (JobsException $exception) {
            return $this->failedMessageResponse(__($exception->getTranslationKey()), $exception->getHttpStatusCode());
        } catch (Throwable $throwable) {
            report($throwable);

            return $this->failedMessageResponse(__('something went wrong'));
        }
    }

    public function destroy(JobOffer $job): JsonResponse
    {
        /** @var HasJobs $user */
        $user = auth()->user();

        try {
            $this->service->delete($job, $user);

            return $this->successMessageResponse(__('job offer deleted successfully'));
        } catch (JobsException $exception) {
            return $this->failedMessageResponse(__($exception->getTranslationKey()), $exception->getHttpStatusCode());
        } catch (Throwable $throwable) {
            report($throwable);

            return $this->failedMessageResponse(__('something went wrong'));
        }
    }

    public function show(JobOffer $job): JsonResponse
    {
        return $this->successResponse(JobResource::make(
            $this->service->show($job)
        ));
    }

    public function deleteMedia(JobOffer $job, Media $media): JsonResponse
    {
        /** @var HasJobs $user */
        $user = auth()->user();

        try {
            $this->service->deleteMedia($job, $media, $user);

            return $this->successMessageResponse(__('media deleted successfully'));
        } catch (JobsException $exception) {
            return $this->failedMessageResponse(__($exception->getTranslationKey()), $exception->getHttpStatusCode());
        } catch (Throwable $throwable) {
            report($throwable);

            return $this->failedMessageResponse(__('something went wrong'));
        }
    }
}
