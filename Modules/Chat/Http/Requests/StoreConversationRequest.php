<?php

namespace Modules\Chat\Http\Requests;

use App\Http\Requests\ApiRequest;

class StoreConversationRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'socket_id' => ['required', 'string', function ($attribute, $value, $fail) {
                if (! preg_match('/^[a-zA-Z]*\-\d+$/i', $value)) {
                    $fail('The socket ID must be in the format "string-number".');

                    return;
                }
            }],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
