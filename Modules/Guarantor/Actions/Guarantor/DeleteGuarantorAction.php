<?php

namespace Modules\Guarantor\Actions\Guarantor;

use Illuminate\Support\Facades\DB;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Exceptions\GuarantorException;
use Modules\Guarantor\Models\GuarantorRequest;
use Throwable;

class DeleteGuarantorAction
{
    /**
     * @throws Throwable
     */
    public function handle(GuarantorRequest $request): void
    {
        DB::transaction(function () use ($request) {
            if ($request->status->isNot(GuarantorStatusEnum::New)) {
                throw new GuarantorException('guarantor.cannot_delete_non_new', 422);
            }

            $request->delete();
        });
    }
}
