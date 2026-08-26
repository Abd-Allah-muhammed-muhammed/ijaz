<?php

namespace Modules\Guarantor\Http\Requests;

use App\Http\Requests\ApiRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Modules\Guarantor\Models\GuarantorRequest;

class UpdateGuarantorRequest extends ApiRequest
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
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string', 'max:2000'],
            'amount' => ['sometimes', 'numeric', 'min:1'],
            'project_type' => ['sometimes', 'nullable', 'string', 'max:255'],
            'files' => ['sometimes', 'nullable', 'array'],
            'files.*' => ['file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            if (! $this->has('amount')) {
                return;
            }

            $guarantorRequest = $this->route('guarantorRequest');

            if (! $guarantorRequest instanceof GuarantorRequest || ! $guarantorRequest->isCompany()) {
                return;
            }

            $guarantorRequest->loadMissing('installments');
            $installmentSum = (float) $guarantorRequest->installments->sum('amount');
            $requestedAmount = (float) $this->input('amount');

            if (round($installmentSum, 2) !== round($requestedAmount, 2)) {
                $v->errors()->add('amount', __('guarantor.installments_sum_mismatch'));
            }
        });
    }
}
