<?php

namespace Modules\Guarantor\Http\Requests;

use App\Rules\CheckAuthenticatableId;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;
use MMAE\ApiResponse\Request\ApiRequest;
use Modules\Guarantor\Enums\AuthorizationTypeEnum;

class StoreCompanyGuarantorRequest extends ApiRequest
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
        return [
            'counterparty_phone' => [
                'required',
                'string',
                new CheckAuthenticatableId('user'),
            ],
            'project_type' => ['nullable', 'string', 'max:255'],
            'total_amount' => ['required', 'numeric', 'min:1'],
            'installments' => ['required', 'array', 'min:1', 'max:12'],
            'installments.*.order' => ['required', 'integer', 'min:1'],
            'installments.*.amount' => ['required', 'numeric', 'min:1'],
            'installments.*.due_date' => ['required', 'date', 'after:today'],
            'company_name' => ['required', 'string', 'max:255'],
            'commercial_register' => ['required', 'string', 'max:255'],
            'region_id' => ['nullable', 'exists:regions,id'],
            'city_id' => ['nullable', 'exists:cities,id'],
            'authorized_name' => ['required', 'string', 'max:255'],
            'authorized_id_number' => ['required', 'string', 'max:50'],
            'authorization_type' => [
                'required',
                Rule::enum(AuthorizationTypeEnum::class),
            ],
            'requester_account_holder' => ['required', 'string', 'max:255'],
            'requester_iban' => ['required', 'string', 'max:50'],
            'counterparty_account_holder' => ['required', 'string', 'max:255'],
            'counterparty_iban' => ['nullable', 'string', 'max:50'],
            'signature' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'authorized_id' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'contracts' => ['required', 'array', 'min:1'],
            'contracts.*' => ['file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
            'iban_certificate' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'company_documents' => ['nullable', 'array'],
            'company_documents.*' => ['file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $installments = $this->input('installments', []);
            $total = collect($installments)->sum('amount');
            $expected = (float) $this->input('total_amount', 0);

            if (round($total, 2) !== round($expected, 2)) {
                $v->errors()->add(
                    'installments',
                    __('guarantor.installments_sum_mismatch')
                );
            }
        });
    }
}
