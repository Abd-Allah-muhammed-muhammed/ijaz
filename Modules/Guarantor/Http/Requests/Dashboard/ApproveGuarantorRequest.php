<?php

namespace Modules\Guarantor\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;

class ApproveGuarantorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
