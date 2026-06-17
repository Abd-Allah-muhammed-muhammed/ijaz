<?php

namespace Modules\Guarantor\Actions\Guarantor;

use App\Models\User;
use App\Services\Sms\Phone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Guarantor\Contracts\Repositories\GuarantorRepositoryInterface;
use Modules\Guarantor\Contracts\Repositories\InstallmentRepositoryInterface;
use Modules\Guarantor\DTOs\CompanyDetailData;
use Modules\Guarantor\DTOs\GuarantorData;
use Modules\Guarantor\DTOs\InstallmentData;
use Modules\Guarantor\Enums\AuthorizationTypeEnum;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Enums\GuarantorTypeEnum;
use Modules\Guarantor\Exceptions\GuarantorException;
use Modules\Guarantor\Models\GuarantorCompanyDetail;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Guarantor\Notifications\GuarantorCreatedNotification;
use Throwable;

class CreateCompanyGuarantorAction
{
    public function __construct(
        private readonly GuarantorRepositoryInterface $guarantorRepository,
        private readonly InstallmentRepositoryInterface $installmentRepository,
        private readonly LogGuarantorStatusHistoryAction $logStatusHistory,
    ) {}

    /**
     * @param  InstallmentData[]  $installments
     *
     * @throws Throwable
     */
    public function handle(
        GuarantorData $data,
        CompanyDetailData $companyData,
        array $installments,
        Model $requester,
        Request $request,
    ): GuarantorRequest {
        return DB::transaction(function () use ($data, $companyData, $installments, $requester, $request) {
            $counterparty = $this->resolveCounterparty($data->counterparty_phone);

            if ($counterparty->getKey() === $requester->getKey() && $counterparty::class === $requester::class) {
                throw new GuarantorException('guarantor.unauthorized', 403);
            }

            $guarantorRequest = $this->guarantorRepository->create([
                'type' => GuarantorTypeEnum::Company,
                'requester_type' => $requester::class,
                'requester_id' => $requester->getKey(),
                'counterparty_type' => $counterparty::class,
                'counterparty_id' => $counterparty->getKey(),
                'title' => $data->title,
                'description' => $data->description,
                'amount' => $data->amount,
                'project_type' => $data->project_type,
                'status' => GuarantorStatusEnum::New,
            ]);

            /** @var GuarantorCompanyDetail $companyDetail */
            $companyDetail = $guarantorRequest->companyDetail()->create([
                'company_name' => $companyData->company_name,
                'commercial_register' => $companyData->commercial_register,
                'region_id' => $companyData->region_id,
                'city_id' => $companyData->city_id,
                'authorized_name' => $companyData->authorized_name,
                'authorized_id_number' => $companyData->authorized_id_number,
                'authorization_type' => AuthorizationTypeEnum::from($companyData->authorization_type),
                'requester_account_holder' => $companyData->requester_account_holder,
                'requester_iban' => $companyData->requester_iban,
                'counterparty_account_holder' => $companyData->counterparty_account_holder,
                'counterparty_iban' => $companyData->counterparty_iban,
            ]);

            foreach ($installments as $installmentData) {
                $this->installmentRepository->create([
                    'guarantor_request_id' => $guarantorRequest->id,
                    'order' => $installmentData->order,
                    'amount' => $installmentData->amount,
                    'due_date' => $installmentData->due_date,
                ]);
            }

            if ($request->hasFile('signature')) {
                $guarantorRequest->addMedia($request->file('signature'))
                    ->toMediaCollection('files');
            }

            if ($request->hasFile('authorized_id')) {
                $companyDetail->addMedia($request->file('authorized_id'))
                    ->toMediaCollection('authorized_id');
            }

            foreach ($request->file('contracts', []) as $contract) {
                $companyDetail->addMedia($contract)->toMediaCollection('contracts');
            }

            if ($request->hasFile('iban_certificate')) {
                $companyDetail->addMedia($request->file('iban_certificate'))
                    ->toMediaCollection('iban_certificates');
            }

            foreach ($request->file('company_documents', []) as $document) {
                $companyDetail->addMedia($document)->toMediaCollection('company_documents');
            }

            $this->logStatusHistory->handle(
                $guarantorRequest,
                $requester,
                null,
                GuarantorStatusEnum::New->value,
            );

            $guarantorRequest->load([
                'requester',
                'counterparty',
                'installments',
                'companyDetail.media',
                'media',
            ]);

            $guarantorRequest->counterparty->notify(
                new GuarantorCreatedNotification($guarantorRequest)
            );

            return $guarantorRequest;
        });
    }

    private function resolveCounterparty(string $phone): User
    {
        $counterparty = User::query()
            ->where('phone', (string) Phone::make($phone))
            ->first();

        if ($counterparty === null) {
            throw new GuarantorException('guarantor.counterparty_not_found', 422);
        }

        return $counterparty;
    }
}
