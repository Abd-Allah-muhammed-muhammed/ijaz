<?php

namespace Modules\Guarantor\Http\Resources\Api;

use App\Http\Resources\Api\V1\MediaResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Catalog\Http\Resources\Api\V1\BankResource;
use Modules\Guarantor\Models\GuarantorCompanyDetail;

/** @mixin GuarantorCompanyDetail */
class CompanyDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_name' => $this->company_name,
            'commercial_register' => $this->commercial_register,
            'authorized_name' => $this->authorized_name,
            'authorized_id_number' => $this->authorized_id_number,
            'authorization_type' => $this->authorization_type->toArray(),
            'requester_account_holder' => $this->requester_account_holder,
            'requester_iban' => $this->requester_iban,
            'requester_bank' => $this->whenLoaded(
                'requesterBank',
                fn () => ($bank = $this->requesterBank) ? BankResource::make($bank) : null,
            ),
            'counterparty_account_holder' => $this->counterparty_account_holder,
            'counterparty_iban' => $this->counterparty_iban,
            'counterparty_bank' => $this->whenLoaded(
                'counterpartyBank',
                fn () => ($bank = $this->counterpartyBank) ? BankResource::make($bank) : null,
            ),
            'terms_notes' => $this->terms_notes,
            'region' => $this->whenLoaded('region'),
            'city' => $this->whenLoaded('city'),
            'media' => $this->whenLoaded('media', fn () => MediaResource::collection($this->media)),
            'requester_documents' => [
                'iban_certificate' => $this->formatDocumentMedia('requester_iban_certificate'),
                'cr_file' => $this->formatDocumentMedia('requester_cr_file'),
                'articles_of_association' => $this->formatDocumentMedia('requester_articles_of_association'),
                'national_address_file' => $this->formatDocumentMedia('requester_national_address_file'),
            ],
            'counterparty_documents' => [
                'iban_certificate' => $this->formatDocumentMedia('counterparty_iban_certificate'),
                'cr_file' => $this->formatDocumentMedia('counterparty_cr_file'),
                'articles_of_association' => $this->formatDocumentMedia('counterparty_articles_of_association'),
                'national_address_file' => $this->formatDocumentMedia('counterparty_national_address_file'),
            ],
        ];
    }

    private function formatDocumentMedia(string $collectionName): ?MediaResource
    {
        return $this->whenLoaded('media', function () use ($collectionName) {
            $media = $this->getFirstMedia($collectionName);

            return $media ? MediaResource::make($media) : null;
        }, null);
    }
}
