<?php

namespace Modules\Guarantor\Actions\Guarantor;

use Illuminate\Support\Facades\DB;
use Modules\Guarantor\Contracts\Repositories\GuarantorRepositoryInterface;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Exceptions\GuarantorException;
use Modules\Guarantor\Models\GuarantorRequest;
use Throwable;

class DeleteGuarantorAction
{
    public function __construct(
        private readonly GuarantorRepositoryInterface $guarantorRepository,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(GuarantorRequest $request): void
    {
        DB::transaction(function () use ($request) {
            if ($request->status->isNot(GuarantorStatusEnum::PendingAdmin)) {
                throw new GuarantorException('guarantor.cannot_delete_non_new', 422);
            }

            $this->guarantorRepository->delete($request);
        });
    }
}
