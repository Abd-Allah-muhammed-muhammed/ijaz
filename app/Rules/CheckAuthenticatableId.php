<?php

namespace App\Rules;

use App\Models\Provider;
use App\Models\User;
use App\Services\Sms\Phone;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class CheckAuthenticatableId implements ValidationRule
{
    public function __construct(protected string $type, protected ?string $attribute = null) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $user = auth()->user();
        $phone = Phone::make($value);
        $exists = match ($this->type) {
            'user' => User::where($this->attribute ?? $attribute, $phone)
                ->when(get_class($user) === User::class, fn ($q) => $q->where('id', '!=', $user->id))
                ->exists(),
            'provider' => Provider::where($this->attribute ?? $attribute, $phone)
                ->when(get_class($user) === Provider::class, fn ($q) => $q->where('id', '!=', $user->id))
                ->exists(),
            default => false,
        };

        if (! $exists) {
            $fail(__('validation.invalid_authenticatable_id', ['authenticatable' => $this->type ?: 'user']));
        }
    }
}
