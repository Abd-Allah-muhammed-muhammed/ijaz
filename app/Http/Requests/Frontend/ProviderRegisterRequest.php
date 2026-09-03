<?php

namespace App\Http\Requests\Frontend;

use App\Models\Provider;
use App\Rules\SaudiIban;
use App\Rules\ValidProviderRegistrationOtpRule;
use App\Support\Phone;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Marketplace\Models\ProviderType;

class ProviderRegisterRequest extends FormRequest
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
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'provider_type_id' => ['required', 'exists:provider_types,id'],
            'region_id' => ['required', 'exists:regions,id'],
            'city_id' => ['required', 'exists:cities,id'],
            'address' => ['required', 'string', 'max:500'],
            'phone' => ['required', 'string', function ($attribute, $value, $fail) {
                $x = Phone::make($value);
                $att = trans('phone');
                if (! $x->isValid()) {
                    $fail(trans('validation.regex', ['attribute' => $att]));
                }
                $exists = Provider::whereIn('phone', $x->all())
                    ->exists();
                if ($exists) {
                    $fail(trans('validation.unique', ['attribute' => $att]));
                }
            }],
            'email' => ['required', 'email', 'max:255', Rule::unique('providers', 'email')],
            'iban' => ['required', 'string', 'max:24', new SaudiIban, Rule::unique('providers', 'iban')],
            'about' => ['required', 'string', 'max:1000'],
            'password' => ['required', 'string', 'min:8', 'max:64', 'confirmed:password_confirmation'],
            'categories' => ['required', 'array'],
            'categories.*.id' => ['required', 'exists:categories,id'],
            'categories.*.skills' => ['sometimes', 'array'],
            'categories.*.skills.*' => ['sometimes', 'exists:skills,id'],
        ];

        // File uploads and OTP are final-submit concerns. Precognition uses
        // Precognition-Validate-Only for field filtering; also skip these
        // entirely on any precognitive request so an unscoped validate never
        // demands logo/OTP mid-wizard.
        if (! $this->isPrecognitive()) {
            $rules['logo'] = ['required', 'image', 'max:8192'];
            $rules['otp'] = ['required', new ValidProviderRegistrationOtpRule];

            $providerType = $this->get('provider_type_id') ? ProviderType::find($this->get('provider_type_id')) : null;
            if ($providerType) {
                $files = array_keys(array_filter($providerType->files));
                foreach ($files as $file) {
                    $rules[$file] = ['required', 'mimetypes:image/*,application/pdf', 'max:8192'];
                }
            }
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'phone' => __('phone'),
            'email' => __('email'),
            'iban' => __('iban'),
        ];
    }
}
