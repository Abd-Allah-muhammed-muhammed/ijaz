<?php

namespace App\Http\Requests\Api\V1\User;

use App\Models\User;
use App\Rules\ValidPhoneRule;
use Illuminate\Contracts\Validation\ValidationRule;
use MMAE\ApiResponse\Request\ApiRequest;

class RegisterRequest extends ApiRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'f_name' => ['required', 'string', 'max:255'],
            'l_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['nullable'],
            'phone' => ['required', 'string', new ValidPhoneRule(new User)],
            'nationality_id' => ['required', 'exists:nationalities,id'],
            'image' => ['required', 'image', 'max:2048'],
            'latitude' => ['required'],
            'longitude' => ['required'],
        ];
    }
}
