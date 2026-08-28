<?php

namespace Modules\Guarantor\Http\Requests;

use App\Http\Requests\ApiRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\Guarantor\Enums\GuarantorTypeEnum;
use Modules\Guarantor\Models\GuarantorRequest;

class AcceptGuarantorRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'signature' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,pdf',
                'max:5120',
            ],
        ];

        $guarantorRequest = $this->route('guarantorRequest');

        if ($guarantorRequest instanceof GuarantorRequest
            && $guarantorRequest->type === GuarantorTypeEnum::Company
        ) {
            $kycDocumentRules = ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'];

            $rules['iban_certificate'] = $kycDocumentRules;
            $rules['cr_file'] = $kycDocumentRules;
            $rules['articles_of_association'] = $kycDocumentRules;
            $rules['national_address_file'] = $kycDocumentRules;
        }

        return $rules;
    }
}
