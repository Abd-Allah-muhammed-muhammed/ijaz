<?php

namespace Modules\Jobs\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Jobs\Contracts\Repositories\JobRepositoryInterface;
use Modules\Jobs\Models\JobOffer;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class JobRepository implements JobRepositoryInterface
{
    /**
     * @param  array{search?: string|null, per_page?: int|null}  $filters
     */
    public function listPublic(array $filters): LengthAwarePaginator
    {
        $search = $filters['search'] ?? null;
        $perPage = (int) ($filters['per_page'] ?? 15);

        return JobOffer::query()
            ->latest()
            ->when(filled($search), function (Builder $query) use ($search) {
                $query->where('title', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%');
            })
            ->with(['city.translation', 'region.translation', 'nationality.translation', 'skills.translation', 'user', 'media'])
            ->active()
            ->paginate($perPage);
    }

    /**
     * @param  array{per_page?: int|null}  $filters
     */
    public function listByActor(Model $actor, array $filters): LengthAwarePaginator
    {
        $perPage = (int) ($filters['per_page'] ?? 15);

        return JobOffer::query()
            ->whereMorphedTo('user', $actor)
            ->latest()
            ->with(['city.translation', 'region.translation', 'nationality.translation', 'skills.translation'])
            ->active()
            ->paginate($perPage);
    }

    public function findById(int $id): JobOffer
    {
        return JobOffer::query()->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Model $actor, array $data): JobOffer
    {
        return JobOffer::query()->create([
            ...$data,
            'user_type' => $actor::class,
            'user_id' => $actor->getKey(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(JobOffer $job, array $data): JobOffer
    {
        $job->update($data);

        return $job->fresh() ?? $job;
    }

    public function delete(JobOffer $job): void
    {
        $job->media->each->delete();
        $job->delete();
    }

    public function deleteMedia(JobOffer $job, Media $media): void
    {
        $media->delete();
    }

    public function loadForShow(JobOffer $job): JobOffer
    {
        return $job->load([
            'city.translation',
            'region.translation',
            'nationality.translation',
            'skills.translation',
            'media',
        ]);
    }

    public function loadForActorList(JobOffer $job): JobOffer
    {
        return $job->load([
            'city.translation',
            'region.translation',
            'nationality.translation',
            'skills.translation',
            'media',
        ]);
    }
}
