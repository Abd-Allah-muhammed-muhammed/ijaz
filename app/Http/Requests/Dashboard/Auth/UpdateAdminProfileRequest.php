<?php

namespace App\Http\Requests\Dashboard\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAdminProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $adminId = auth('admin')->id();

        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:255', Rule::unique('admins', 'phone')->ignore($adminId)],
            'email' => ['required', 'email', 'max:254', Rule::unique('admins', 'email')->ignore($adminId)],
            'address' => ['required', 'string', 'max:500'],
            'job' => ['required', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'image' => ['nullable', 'image', 'max:2048'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('password') === '' || $this->input('password') === null) {
            $this->merge([
                'password' => null,
                'password_confirmation' => null,
            ]);
        }
    }
}
