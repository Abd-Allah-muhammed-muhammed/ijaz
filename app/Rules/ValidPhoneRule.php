<?php

namespace App\Rules;

use App\Services\Sms\Phone;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Translation\PotentiallyTranslatedString;

readonly class ValidPhoneRule implements ValidationRule
{
    /**
     * @param  ?Model  $model  = null
     */
    public function __construct(
        private ?Model $model = null,
        private ?string $column = null,
        private bool $existance = true,
        private ?string $code = 'KSA'
    ) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($this->code)) {
            $fail(trans('validation.exists', ['attribute' => trans('phone')]));

            return;
        }

        if (empty($value)) {
            $fail(trans('validation.required', ['attribute' => trans('phone')]));

            return;

        }
        $phone = Phone::make($value, $this->code);
        if ($phone->isNotValid()) {
            $fail(trans('messages.invalid_phone'));

            return;
        }
        if ($this->existance) {
            $col = empty($this->column) ? $attribute : $this->column;
            $idCol = $this->model->getKeyName();
            $id = $this->model->getAttribute($idCol);
            $check = $this
                ->model::query()
                ->when(method_exists($this->model, 'bootSoftDeletes'), fn (Builder $query) => $query->withTrashed())
                ->whereIn($col, $phone->all())
                ->when($id, fn (Builder $query, $v) => $query->where($idCol, '<>', $v))
                ->exists();
            if ($check) {
                $fail(trans('validation.unique', ['attribute' => trans('phone')]));
            }
        }

    }
}
