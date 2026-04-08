<?php

namespace App\Http\Requests\Dashboard;

use App\Enums\OperationStatusEnum;
use Illuminate\Foundation\Http\FormRequest;

class UpdateWithdrawRequestStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => 'required|in:'.OperationStatusEnum::Approved->value.','.OperationStatusEnum::Rejected->value,
            'admin_notes' => 'nullable|string',
        ];
    }
}
