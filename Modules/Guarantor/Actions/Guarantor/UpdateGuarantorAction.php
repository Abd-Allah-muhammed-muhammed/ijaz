<?php

namespace Modules\Guarantor\Actions\Guarantor;

use Illuminate\Support\Facades\DB;
use Modules\Guarantor\Contracts\Repositories\GuarantorRepositoryInterface;
use Modules\Guarantor\DTOs\GuarantorUploadData;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Exceptions\GuarantorException;
use Modules\Guarantor\Models\GuarantorRequest;
use Throwable;

class UpdateGuarantorAction
{
    public function __construct(
        private readonly GuarantorRepositoryInterface $guarantorRepository,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws Throwable
     */
    public function handle(
        GuarantorRequest $request,
        array $data,
        GuarantorUploadData $uploads,
    ): GuarantorRequest {
        return DB::transaction(function () use ($request, $data, $uploads) {
            if ($request->status->isNot(GuarantorStatusEnum::PendingAdmin)) {
                throw new GuarantorException('guarantor.cannot_update_non_new', 422);
            }

            $guarantorRequest = $this->guarantorRepository->update($request, $data);

            foreach ($uploads->files as $file) {
                $guarantorRequest->addMedia($file)->toMediaCollection('files');
            }

            return $guarantorRequest->load(['requester', 'counterparty', 'installments', 'companyDetail', 'media']);
        });
    }
}
