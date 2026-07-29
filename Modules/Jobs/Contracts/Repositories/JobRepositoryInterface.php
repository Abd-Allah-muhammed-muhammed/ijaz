<?php

namespace Modules\Jobs\Contracts\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Modules\Jobs\Models\JobOffer;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

interface JobRepositoryInterface
{
    /**
     * @param  array{search?: string|null, per_page?: int|null}  $filters
     */
    public function listPublic(array $filters): LengthAwarePaginator;

    /**
     * @param  array{per_page?: int|null}  $filters
     */
    public function listByActor(Model $actor, array $filters): LengthAwarePaginator;

    public function findById(int $id): JobOffer;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Model $actor, array $data): JobOffer;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(JobOffer $job, array $data): JobOffer;

    public function delete(JobOffer $job): void;

    public function deleteMedia(JobOffer $job, Media $media): void;

    public function loadForShow(JobOffer $job): JobOffer;

    public function loadForActorList(JobOffer $job): JobOffer;
}
