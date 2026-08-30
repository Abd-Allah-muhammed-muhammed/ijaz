<?php

namespace Modules\Opportunity\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;

class ApproveOpportunityRequest extends FormRequest
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
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
