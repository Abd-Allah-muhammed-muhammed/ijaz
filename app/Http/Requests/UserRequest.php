<?php

namespace App\Http\Requests;

use App\Models\RegisterVerificationCode;
use App\Models\User;
use App\Services\Sms\Phone;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
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

        return [
            'f_name' => ['required', 'string', 'max:255'],
            'l_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($id)],
            'password' => [Rule::when($id, 'nullable', 'required'), 'string', 'max:20', 'confirmed:password_confirmation'],
            'phone' => ['required', 'string', function ($attribute, $value, $fail) use ($id) {
                $x = Phone::make($value);
                $att = trans('phone');
                if (! $x->isValid()) {

                    $fail(trans('validation.regex', ['attribute' => $att]));
                }
                $exists = User::whereIn('phone', $x->all())
                    ->when($id, function ($query) use ($id) {
                        return $query->where('id', '!=', $id);
                    })
                    ->exists();
                if ($exists) {
                    $fail(trans('validation.unique', ['attribute' => $att]));
                }
            }],
            'nationality_id' => ['required', 'exists:nationalities,id'],
            'image' => [Rule::when($id, 'nullable', 'required'), 'image', 'max:2048'],

            //      'otp' => ['required', function ($attribute, $value, $fail) {
            //        $otp = RegisterVerificationCode::where('queryable', $this->get('phone'))->first();
            //
            //        if(!$otp || $otp->isExpired()) {
            //          $fail(trans('auth.otp_expired'));
            //        } else if(!$otp->check($this->get('otp'))) {
            //          $fail(trans('auth.otp_invalid'));
            //        }
            //      }],
        ];
    }

    protected function getModelKey(): ?string
    {
        $pram = $this->route('user');
        if (is_null($pram)) {
            return null;
        }
        if ($pram instanceof Model) {
            return $pram->getKey();
        }

        return $pram;
    }
}
