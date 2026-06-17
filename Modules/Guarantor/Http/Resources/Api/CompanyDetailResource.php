<?php

namespace Modules\Guarantor\Http\Resources\Api;

use App\Http\Resources\Api\V1\MediaResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
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
            'counterparty_account_holder' => $this->counterparty_account_holder,
            'counterparty_iban' => $this->counterparty_iban,
            'region' => $this->whenLoaded('region'),
            'city' => $this->whenLoaded('city'),
            'media' => $this->whenLoaded('media', fn () => MediaResource::collection($this->media)),
        ];
    }
}
