<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required'],
            'phone' => ['required', Rule::unique('admins')->ignore($this->route('admin'))],
            'email' => ['required', 'email', 'max:254', Rule::unique('admins')->ignore($this->route('admin'))],
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
}
