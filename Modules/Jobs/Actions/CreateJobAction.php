<?php

namespace Modules\Jobs\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Modules\Jobs\Contracts\Repositories\JobRepositoryInterface;
use Modules\Jobs\DTOs\StoreJobDTO;
use Modules\Jobs\Models\JobOffer;
use Throwable;

class CreateJobAction
{
    public function __construct(
        private readonly JobRepositoryInterface $repository,
    ) {}

    /**
     * @param  array<int, UploadedFile>|null  $files
     *
     * @throws Throwable
     */
    public function handle(Model $actor, StoreJobDTO $dto, ?array $files = null): JobOffer
    {
        return DB::transaction(function () use ($actor, $dto, $files) {
            $job = $this->repository->create($actor, $dto->toPersistenceArray());

            if ($dto->skillIds !== []) {
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
