<?php

namespace App\Http\Requests\Dashboard;

use App\Enums\Users\UserStatusEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UserStatusRequest extends FormRequest
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
            'status' => ['required', new Enum(UserStatusEnum::class)],
            'block_days' => 'nullable|integer',
            'block_reason' => 'nullable|string',
        ];
    }
}
