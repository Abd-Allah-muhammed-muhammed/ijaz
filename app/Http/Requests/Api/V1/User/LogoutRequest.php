<?php

namespace App\Http\Requests\Api\V1\User;

use Illuminate\Foundation\Http\FormRequest;

class LogoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Logout needs no body — the current Sanctum session identifies which
     * device token to clear.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
