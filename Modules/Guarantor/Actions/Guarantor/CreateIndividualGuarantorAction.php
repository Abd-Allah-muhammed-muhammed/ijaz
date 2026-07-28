<?php

namespace Modules\Guarantor\Actions\Guarantor;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\Guarantor\Contracts\Repositories\GuarantorRepositoryInterface;
use Modules\Guarantor\DTOs\GuarantorData;
use Modules\Guarantor\DTOs\GuarantorUploadData;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Enums\GuarantorTypeEnum;
use Modules\Guarantor\Exceptions\GuarantorException;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Guarantor\Notifications\GuarantorCreatedNotification;
use Throwable;

class CreateIndividualGuarantorAction
{
    public function __construct(
        private readonly GuarantorRepositoryInterface $guarantorRepository,
        private readonly LogGuarantorStatusHistoryAction $logStatusHistory,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(
        GuarantorData $data,
        Model $requester,
        GuarantorUploadData $uploads,
    ): GuarantorRequest {
        return DB::transaction(function () use ($data, $requester, $uploads) {
            $counterparty = $this->resolveCounterparty($data->counterparty_phone);

            if ($counterparty->getKey() === $requester->getKey() && $counterparty::class === $requester::class) {
                throw new GuarantorException('guarantor.unauthorized', 403);
            }

            $guarantorRequest = $this->guarantorRepository->create([
                'type' => GuarantorTypeEnum::Individual,
                'requester_type' => $requester::class,
                'requester_id' => $requester->getKey(),
                'counterparty_type' => $counterparty::class,
                'counterparty_id' => $counterparty->getKey(),
                'title' => $data->title,
                'description' => $data->description,
                'amount' => $data->amount,
                'status' => GuarantorStatusEnum::PendingAdmin,
            ]);

            if ($uploads->signature !== null) {
                $guarantorRequest->addMedia($uploads->signature)
                    ->toMediaCollection('signature');
            }

            $this->logStatusHistory->handle(
                $guarantorRequest,
                $requester,
                null,
                GuarantorStatusEnum::PendingAdmin->value,
            );

            $guarantorRequest->load(['requester', 'counterparty', 'media']);

            $guarantorRequest->requester->notify(
                new GuarantorCreatedNotification($guarantorRequest)
            );

            return $guarantorRequest;
        });
    }

    private function resolveCounterparty(string $phone): User
    {
        $counterparty = $this->guarantorRepository->findCounterpartyByPhone($phone);

        if ($counterparty === null) {
            throw new GuarantorException('guarantor.counterparty_not_found', 422);
        }

        return $counterparty;
    }
}
