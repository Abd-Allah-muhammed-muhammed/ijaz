<?php

namespace App\Http\Requests\Dashboard\Auth;

use App\Models\Admin;
use App\Rules\ValidPhoneRule;
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
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20', new ValidPhoneRule($admin)],
            'email' => ['required', 'email', 'max:254', Rule::unique('admins', 'email')->ignore($admin->id)],
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

        if ($this->has('phone') && is_string($this->input('phone'))) {
            $this->merge([
                'phone' => preg_replace('/[\s\-]+/', '', $this->input('phone')),
            ]);
        }
    }
}
