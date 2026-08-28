<?php

namespace Modules\Guarantor\Http\Resources\Api;

use App\Http\Resources\Api\V1\MediaResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Catalog\Http\Resources\Api\V1\BankResource;
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
            'requester_bank' => $this->whenLoaded(
                'requesterBank',
                fn () => $this->requesterBank
                    ? BankResource::make($this->requesterBank)->resolve($request)
                    : null,
            ),
            'counterparty_account_holder' => $this->counterparty_account_holder,
            'counterparty_iban' => $this->counterparty_iban,
            'counterparty_bank' => $this->whenLoaded(
                'counterpartyBank',
                fn () => $this->counterpartyBank
                    ? BankResource::make($this->counterpartyBank)->resolve($request)
                    : null,
            ),
            'terms_notes' => $this->terms_notes,
            'region' => $this->whenLoaded('region'),
            'city' => $this->whenLoaded('city'),
            'media' => $this->whenLoaded('media', fn () => MediaResource::collection($this->media)->resolve($request)),
            'requester_documents' => [
                'iban_certificate' => $this->whenLoaded('media', fn () => ($media = $this->getFirstMedia('requester_iban_certificate')) instanceof Media ? MediaResource::make($media)->resolve($request) : null, null),
                'cr_file' => $this->whenLoaded('media', fn () => ($media = $this->getFirstMedia('requester_cr_file')) instanceof Media ? MediaResource::make($media)->resolve($request) : null, null),
                'articles_of_association' => $this->whenLoaded('media', fn () => ($media = $this->getFirstMedia('requester_articles_of_association')) instanceof Media ? MediaResource::make($media)->resolve($request) : null, null),
                'national_address_file' => $this->whenLoaded('media', fn () => ($media = $this->getFirstMedia('requester_national_address_file')) instanceof Media ? MediaResource::make($media)->resolve($request) : null, null),
            ],
            'counterparty_documents' => [
                'iban_certificate' => $this->whenLoaded('media', fn () => ($media = $this->getFirstMedia('counterparty_iban_certificate')) instanceof Media ? MediaResource::make($media)->resolve($request) : null, null),
                'cr_file' => $this->whenLoaded('media', fn () => ($media = $this->getFirstMedia('counterparty_cr_file')) instanceof Media ? MediaResource::make($media)->resolve($request) : null, null),
                'articles_of_association' => $this->whenLoaded('media', fn () => ($media = $this->getFirstMedia('counterparty_articles_of_association')) instanceof Media ? MediaResource::make($media)->resolve($request) : null, null),
                'national_address_file' => $this->whenLoaded('media', fn () => ($media = $this->getFirstMedia('counterparty_national_address_file')) instanceof Media ? MediaResource::make($media)->resolve($request) : null, null),
            ],
        ];
    }
}
