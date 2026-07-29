<?php

namespace Modules\Guarantor\Actions\Guarantor;

use Illuminate\Support\Facades\DB;
use Modules\Guarantor\Contracts\Repositories\GuarantorRepositoryInterface;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Exceptions\GuarantorException;
use Modules\Guarantor\Models\GuarantorRequest;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Throwable;

class DeleteGuarantorMediaAction
{
    public function __construct(
        private readonly GuarantorRepositoryInterface $guarantorRepository,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(GuarantorRequest $request, Media $media): void
    {
        DB::transaction(function () use ($request, $media) {
            if ($request->status->isNot(GuarantorStatusEnum::PendingAdmin)) {
                throw new GuarantorException('guarantor.cannot_delete_media_non_new', 422);
            }

            $this->guarantorRepository->deleteMedia($media);
        });
    }
}
