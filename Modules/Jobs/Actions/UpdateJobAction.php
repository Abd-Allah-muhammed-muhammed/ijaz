<?php

namespace Modules\Jobs\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Modules\Jobs\Contracts\Repositories\JobRepositoryInterface;
use Modules\Jobs\DTOs\UpdateJobDTO;
use Modules\Jobs\Exceptions\JobsException;
use Modules\Jobs\Http\Controllers\Concerns\AuthorizesJobRequests;
use Modules\Jobs\Models\JobOffer;
use Throwable;

class UpdateJobAction
{
    use AuthorizesJobRequests;

    public function __construct(
        private readonly JobRepositoryInterface $repository,
    ) {}

    /**
     * @param  array<int, UploadedFile>|null  $files
     *
     * @throws JobsException
     * @throws Throwable
     */
    public function handle(JobOffer $job, Model $actor, UpdateJobDTO $dto, ?array $files = null): JobOffer
    {
        $this->ensureJobOwnedBy($job, $actor);

        return DB::transaction(function () use ($job, $dto, $files) {
            $job = $this->repository->update($job, $dto->toPersistenceArray());

            if ($dto->skillIds !== null) {
                $job->skills()->sync($dto->skillIds);
            }

            if (! empty($files)) {
                foreach ($files as $file) {
                    $job->addMedia($file)->toMediaCollection();
                }
            }

            return $this->repository->loadForActorList($job);
        });
    }
}
