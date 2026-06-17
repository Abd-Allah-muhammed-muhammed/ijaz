<?php

namespace Modules\Guarantor\Actions\Guarantor;

use Illuminate\Support\Facades\DB;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Exceptions\GuarantorException;
use Modules\Guarantor\Models\GuarantorRequest;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Throwable;

class DeleteGuarantorMediaAction
{
    /**
     * @throws Throwable
     */
    public function handle(GuarantorRequest $request, Media $media): void
    {
        DB::transaction(function () use ($request, $media) {
            if ($request->status->isNot(GuarantorStatusEnum::New)) {
                throw new GuarantorException('guarantor.cannot_delete_media_non_new', 422);
            }

            $media->delete();
        });
    }
}
