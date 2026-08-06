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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Mobile may still send legacy "player_id" for the FCM registration token.
            'player_id' => ['nullable', 'string', 'max:512'],
            'device_token' => ['nullable', 'string', 'max:512'],
        ];
    }

    public function deviceToken(): ?string
    {
        $token = $this->input('device_token') ?? $this->input('player_id');

        return filled($token) ? (string) $token : null;
    }
}
