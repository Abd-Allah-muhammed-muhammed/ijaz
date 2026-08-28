<?php

namespace Modules\Guarantor\Http\Resources\Api;

use App\Http\Resources\Api\V1\MediaResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Catalog\Models\Bank;
use Modules\Guarantor\Models\GuarantorCompanyDetail;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

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
            'requester_bank' => $this->whenLoaded('requesterBank', fn () => $this->formatBank($this->requesterBank)),
            'counterparty_account_holder' => $this->counterparty_account_holder,
            'counterparty_iban' => $this->counterparty_iban,
            'counterparty_bank' => $this->whenLoaded('counterpartyBank', fn () => $this->formatBank($this->counterpartyBank)),
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

    /**
     * @return array{value: string, label: string, logo_url: string|null}|null
     */
    private function formatBank(?Bank $bank): ?array
    {
        if ($bank === null) {
            return null;
        }

        return [
            'value' => $bank->getValue(),
            'label' => $bank->getLabel(),
            'logo_url' => $bank->getLogoUrl(),
        ];
    }

    /**
     * @return array{id: string, url: string, mime_type: string, file_name: string}|null
     */
    private function formatDocumentMedia(string $collectionName): ?array
    {
        if (! $this->relationLoaded('media')) {
            return null;
        }

        $media = $this->getFirstMedia($collectionName);

        if (! $media instanceof Media) {
            return null;
        }

        return [
            'id' => $media->uuid,
            'url' => $media->getUrl(),
            'mime_type' => $media->mime_type,
            'file_name' => $media->file_name,
        ];
    }
}
