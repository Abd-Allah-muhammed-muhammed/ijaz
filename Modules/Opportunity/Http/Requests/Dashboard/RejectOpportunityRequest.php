<?php

namespace Modules\Opportunity\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;

class RejectOpportunityRequest extends FormRequest
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
            'reason' => ['required', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
