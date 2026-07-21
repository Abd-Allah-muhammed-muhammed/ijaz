<?php

namespace App\Http\Requests;

use App\Models\Provider;
use Modules\Marketplace\Models\ProviderType;
use App\Models\RegisterVerificationCode;
use App\Services\Sms\Phone;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'region_id' => ['nullable', 'exists:regions,id'],
            'city_id' => ['nullable', 'exists:cities,id'],
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
            'iban' => ['required', 'string', 'max:34', Rule::unique('providers', 'iban')],
            'about' => ['required', 'string', 'max:1000'],
            'logo' => ['required', 'image', 'max:8192'],
            'password' => ['required', 'string', 'max:20', 'confirmed:password_confirmation'],
            'categories' => ['required', 'array'],
            'categories.*.id' => ['required', 'exists:categories,id'],
            'categories.*.skills' => ['sometimes', 'array'],
            'categories.*.skills.*' => ['sometimes', 'exists:skills,id'],
            'otp' => ['required', function ($attribute, $value, $fail) {
                $x = Phone::make($this->input('phone'))->toString();
                $otp = RegisterVerificationCode::where('queryable', $x)->first();
                if (! $otp || $otp->isExpired()) {
                    $fail(trans('auth.otp_expired'));
                } elseif (! $otp->check($this->get('otp'))) {
                    $fail(trans('auth.otp_invalid'));
                }
            }],
        ];
        $providerType = $this->get('provider_type_id') ? ProviderType::find($this->get('provider_type_id')) : null;
        if ($providerType) {
            $files = array_keys(array_filter($providerType->files));
            foreach ($files as $file) {
                $rules[$file] = ['required', 'mimetypes:image/*,application/pdf', 'max:8192'];
            }
        }

        return $rules;
    }
}
