<?php

namespace App\Http\Requests\Dashboard;

use App\Enums\Providers\ProviderStatusEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class ProviderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', new Enum(ProviderStatusEnum::class)],
            'block_days' => 'nullable|integer',
            'block_reason' => 'nullable|string',
            'reason' => 'nullable|string',
        ];
    }
}
