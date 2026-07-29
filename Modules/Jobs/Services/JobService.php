<?php

namespace Modules\Jobs\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Modules\Jobs\Actions\CreateJobAction;
use Modules\Jobs\Actions\DeleteJobAction;
use Modules\Jobs\Actions\DeleteJobMediaAction;
use Modules\Jobs\Actions\ListActorJobsAction;
use Modules\Jobs\Actions\ListPublicJobsAction;
use Modules\Jobs\Actions\ShowJobAction;
use Modules\Jobs\Actions\UpdateJobAction;
use Modules\Jobs\DTOs\StoreJobDTO;
use Modules\Jobs\DTOs\UpdateJobDTO;
use Modules\Jobs\Exceptions\JobsException;
use Modules\Jobs\Models\JobOffer;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Throwable;

class JobService
{
    public function __construct(
        private readonly ListPublicJobsAction $listPublicAction,
        private readonly ListActorJobsAction $listActorAction,
        private readonly ShowJobAction $showAction,
        private readonly CreateJobAction $createAction,
        private readonly UpdateJobAction $updateAction,
        private readonly DeleteJobAction $deleteAction,
        private readonly DeleteJobMediaAction $deleteMediaAction,
    ) {}

    /**
     * @param  array{search?: string|null, per_page?: int|null}  $filters
     */
    public function listPublic(array $filters): LengthAwarePaginator
    {
        return $this->listPublicAction->handle($filters);
    }

    /**
     * @param  array{per_page?: int|null}  $filters
     */
    public function listByActor(Model $actor, array $filters): LengthAwarePaginator
    {
        return $this->listActorAction->handle($actor, $filters);
    }

    public function show(JobOffer $job): JobOffer
    {
        return $this->showAction->handle($job);
    }

    /**
     * @param  array<int, UploadedFile>|null  $files
     *
     * @throws Throwable
     */
    public function create(Model $actor, StoreJobDTO $dto, ?array $files = null): JobOffer
    {
        return $this->createAction->handle($actor, $dto, $files);
    }

    /**
     * @param  array<int, UploadedFile>|null  $files
     *
     * @throws JobsException
     * @throws Throwable
     */
    public function update(JobOffer $job, Model $actor, UpdateJobDTO $dto, ?array $files = null): JobOffer
    {
        return $this->updateAction->handle($job, $actor, $dto, $files);
    }

    /**
     * @throws JobsException
     */
    public function delete(JobOffer $job, Model $actor): void
    {
        $this->deleteAction->handle($job, $actor);
    }

    /**
     * @throws JobsException
     */
    public function deleteMedia(JobOffer $job, Media $media, Model $actor): void
    {
        $this->deleteMediaAction->handle($job, $media, $actor);
    }
}
