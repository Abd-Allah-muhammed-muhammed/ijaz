<?php

namespace App\Http\Requests\Dashboard;

use App\Models\Admin;
use App\Rules\ValidPhoneRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminRequest extends FormRequest
{
    public function rules(): array
    {
        $admin = $this->route('admin');
        $adminModel = $admin instanceof Admin ? $admin : new Admin;

        return [
            'name' => ['required'],
            'phone' => ['required', 'string', 'max:20', new ValidPhoneRule($adminModel)],
            'email' => ['required', 'email', 'max:254', Rule::unique('admins')->ignore($admin instanceof Admin ? $admin : null)],
            'password' => [Rule::when($this->route('admin'), ['nullable'], ['required']), 'confirmed', 'min:8'],
            'image' => [Rule::when($this->route('admin'), ['nullable'], ['required']), 'image', 'max:2048'],
            'address' => ['required'],
            'job' => ['required'],
            'roles' => ['required', 'array'],
            'roles.*' => ['required', Rule::exists('roles', 'id')->where('guard_name', 'admin')],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('phone') || ! is_string($this->input('phone'))) {
            return;
        }

        $this->merge([
            'phone' => preg_replace('/[\s\-]+/', '', $this->input('phone')),
        ]);
    }
}
