<?php

namespace App\Http\Requests\Dashboard;

use App\Models\Provider;
use App\Models\ProviderType;
use App\Services\Sms\Phone;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProviderRequest extends FormRequest
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
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        $id = $this->getModelKey();
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'provider_type_id' => ['required', 'exists:provider_types,id'],
            'region_id' => ['required', 'exists:regions,id'],
            'city_id' => ['required', 'exists:cities,id'],
            'address' => ['required', 'string', 'max:500'],
            'phone' => ['required', 'string', function ($attribute, $value, $fail) use ($id) {
                $x = Phone::make($value);
                $att = trans('phone');
                if (! $x->isValid()) {

                    $fail(trans('validation.regex', ['attribute' => $att]));
                }
                $exists = Provider::whereIn('phone', $x->all())
                    ->when($id, function ($query) use ($id) {
                        return $query->where('id', '!=', $id);
                    })
                    ->exists();
                if ($exists) {
                    $fail(trans('validation.unique', ['attribute' => $att]));
                }
            }],
            'email' => ['required', 'email', 'max:255', Rule::unique('providers', 'email')->ignore($id)],
            'iban' => ['required', 'string', 'max:34', Rule::unique('providers', 'iban')->ignore($id)],
            'about' => ['required', 'string', 'max:1000'],
            'logo' => [Rule::when($id, 'nullable', 'required'), 'image', 'max:2048'],
            'password' => [Rule::when($id, 'nullable', 'required'), 'string', 'max:20', 'confirmed:password_confirmation'],
            'categories' => ['required', 'array'],
            'categories.*.id' => ['required', 'exists:categories,id'],
            'categories.*.skills' => ['nullable', 'array'],
            'categories.*.skills.*' => ['nullable', 'exists:skills,id'],
        ];
        $type = $this->get('provider_type_id') ? ProviderType::find($this->get('provider_type_id')) : null;
        if ($type) {
            $files = array_keys(array_filter($type->files));
            foreach ($files as $file) {
                $rules[$file] = [Rule::when($id, 'nullable', 'required'), 'mimetypes:application/pdf', 'max:8192'];
            }
        }

        return $rules;
    }

    protected function getModelKey(): ?string
    {
        $pram = $this->route('provider');
        if (is_null($pram)) {
            return null;
        }
        if ($pram instanceof Model) {
            return $pram->getKey();
        }

        return $pram;
    }
}
