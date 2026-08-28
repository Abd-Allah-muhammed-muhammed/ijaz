<?php

namespace Modules\Guarantor\Actions\Guarantor;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\Guarantor\Actions\Guarantor\NotifyAdminsOfGuarantorPendingAction as NotifyAdminsOfGuarantorPending;
use Modules\Guarantor\Contracts\Repositories\CompanyDetailRepositoryInterface;
use Modules\Guarantor\Contracts\Repositories\GuarantorRepositoryInterface;
use Modules\Guarantor\Contracts\Repositories\InstallmentRepositoryInterface;
use Modules\Guarantor\DTOs\CompanyDetailData;
use Modules\Guarantor\DTOs\GuarantorData;
use Modules\Guarantor\DTOs\GuarantorUploadData;
use Modules\Guarantor\DTOs\InstallmentData;
use Modules\Guarantor\Enums\AuthorizationTypeEnum;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Enums\GuarantorTypeEnum;
use Modules\Guarantor\Exceptions\GuarantorException;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Guarantor\Notifications\GuarantorCreatedNotification;
use Throwable;

class CreateCompanyGuarantorAction
{
    public function __construct(
        private readonly GuarantorRepositoryInterface $guarantorRepository,
        private readonly CompanyDetailRepositoryInterface $companyDetailRepository,
        private readonly InstallmentRepositoryInterface $installmentRepository,
        private readonly LogGuarantorStatusHistoryAction $logStatusHistory,
        private readonly NotifyAdminsOfGuarantorPending $notifyAdminsOfGuarantorPendingAction,
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
        GuarantorUploadData $uploads,
    ): GuarantorRequest {
        return DB::transaction(function () use ($data, $companyData, $installments, $requester, $uploads) {
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
                'status' => GuarantorStatusEnum::PendingAdmin,
            ]);

            $companyDetail = $this->companyDetailRepository->createForGuarantor($guarantorRequest, [
                'company_name' => $companyData->company_name,
                'commercial_register' => $companyData->commercial_register,
                'region_id' => $companyData->region_id,
                'city_id' => $companyData->city_id,
                'authorized_name' => $companyData->authorized_name,
                'authorized_id_number' => $companyData->authorized_id_number,
                'authorization_type' => AuthorizationTypeEnum::from($companyData->authorization_type),
                'requester_account_holder' => $companyData->requester_account_holder,
                'requester_iban' => $companyData->requester_iban,
                'requester_bank_id' => $companyData->requester_bank_id,
                'counterparty_account_holder' => $companyData->counterparty_account_holder,
                'counterparty_iban' => $companyData->counterparty_iban,
                'counterparty_bank_id' => $companyData->counterparty_bank_id,
                'terms_notes' => $companyData->terms_notes,
            ]);

            foreach ($installments as $installmentData) {
                $this->installmentRepository->create([
                    'guarantor_request_id' => $guarantorRequest->id,
                    'order' => $installmentData->order,
                    'amount' => $installmentData->amount,
                    'due_date' => $installmentData->due_date,
                ]);
            }

            if ($uploads->signature !== null) {
                $guarantorRequest->addMedia($uploads->signature)
                    ->toMediaCollection('requester_signature');
            }

            if ($uploads->authorizedId !== null) {
                $companyDetail->addMedia($uploads->authorizedId)
                    ->toMediaCollection('authorized_id');
            }

            foreach ($uploads->contracts as $contract) {
                $companyDetail->addMedia($contract)->toMediaCollection('contracts');
            }

            if ($uploads->ibanCertificate !== null) {
                $companyDetail->addMedia($uploads->ibanCertificate)
                    ->toMediaCollection('requester_iban_certificate');
            }

            if ($uploads->crFile !== null) {
                $companyDetail->addMedia($uploads->crFile)
                    ->toMediaCollection('requester_cr_file');
            }

            if ($uploads->articlesOfAssociation !== null) {
                $companyDetail->addMedia($uploads->articlesOfAssociation)
                    ->toMediaCollection('requester_articles_of_association');
            }

            if ($uploads->nationalAddressFile !== null) {
                $companyDetail->addMedia($uploads->nationalAddressFile)
                    ->toMediaCollection('requester_national_address_file');
            }

            if ($uploads->agencyAuthorizationDocument !== null) {
                $companyDetail->addMedia($uploads->agencyAuthorizationDocument)
                    ->toMediaCollection('agency_authorization_document');
            }

            foreach ($uploads->companyDocuments as $document) {
                $companyDetail->addMedia($document)->toMediaCollection('company_documents');
            }

            $this->logStatusHistory->handle(
                $guarantorRequest,
                $requester,
                null,
                GuarantorStatusEnum::PendingAdmin->value,
            );

            $guarantorRequest->load([
                'requester',
                'counterparty',
                'installments',
                'companyDetail.requesterBank',
                'companyDetail.counterpartyBank',
                'companyDetail.media',
                'media',
            ]);

            $guarantorRequest->requester->notify(
                new GuarantorCreatedNotification($guarantorRequest)
            );

            $this->notifyAdminsOfGuarantorPendingAction->handle($guarantorRequest);

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
