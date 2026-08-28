<?php

namespace Modules\Guarantor\Http\Requests;

use App\Http\Requests\ApiRequest;
use App\Rules\SaudiIban;
use App\Support\Normalize;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;
use Modules\Guarantor\Enums\AuthorizationTypeEnum;
use Modules\Guarantor\Rules\CheckAuthenticatableId;

class StoreCompanyGuarantorRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'counterparty_phone' => [
                'required',
                'string',
                new CheckAuthenticatableId('user'),
            ],
            'project_type' => ['required', 'string', 'max:255'],
            'total_amount' => ['required', 'numeric', 'min:1'],
            'installments' => ['required', 'array', 'min:1', 'max:12'],
            'installments.*.order' => ['required', 'integer', 'min:1'],
            'installments.*.amount' => ['required', 'numeric', 'min:1'],
            'installments.*.due_date' => ['required', 'date', 'after:today'],
            'company_name' => ['required', 'string', 'max:255'],
            'commercial_register' => ['required', 'string', 'max:255'],
            'region_id' => ['nullable', 'exists:regions,id'],
            'city_id' => ['nullable', 'exists:cities,id'],
            'authorized_name' => ['required', 'string', 'max:255'],
            'authorized_id_number' => ['required', 'string', 'max:50'],
            'authorization_type' => [
                'required',
                Rule::enum(AuthorizationTypeEnum::class),
            ],
            'requester_account_holder' => ['required', 'string', 'max:255'],
            'requester_iban' => ['required', 'string', 'max:50', new SaudiIban],
            'counterparty_account_holder' => ['required', 'string', 'max:255'],
            'counterparty_iban' => ['nullable', 'string', 'max:50', new SaudiIban],
            'signature' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'authorized_id' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'contracts' => ['required', 'array', 'min:1'],
            'contracts.*' => ['file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
            'iban_certificate' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'company_documents' => ['nullable', 'array'],
            'company_documents.*' => ['file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'installments.*.due_date.required' => __('guarantor.installment_due_date_required'),
            'installments.*.due_date.date' => __('guarantor.installment_due_date_invalid'),
            'installments.*.due_date.after' => __('guarantor.installment_due_date_after_today'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'installments.*.due_date' => __('guarantor.due_date'),
        ];
    }

    protected function prepareForValidation(): void
    {
        $installments = $this->input('installments');
        if (! is_array($installments)) {
            return;
        }

        $normalized = [];
        foreach ($installments as $index => $installment) {
            if (! is_array($installment)) {
                $normalized[$index] = $installment;

                continue;
            }

            if (isset($installment['due_date']) && is_string($installment['due_date'])) {
                $installment['due_date'] = Normalize::westernDigits($installment['due_date']);
            }

            $normalized[$index] = $installment;
        }

        $this->merge(['installments' => $normalized]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $installments = $this->input('installments', []);

            if (! is_array($installments) || $installments === []) {
                return;
            }

            $orderValid = $this->validateUniqueSequentialOrder($v, $installments);

            $this->validateInstallmentSum($v, $installments);

            if (! $orderValid) {
                return;
            }

            $this->validateFirstInstallmentMaxDays($v, $installments);
            $this->validateInstallmentChronologicalOrder($v, $installments);
        });
    }

    /**
     * @param  list<array<string, mixed>>  $installments
     */
    private function validateUniqueSequentialOrder(Validator $v, array $installments): bool
    {
        $orders = collect($installments)
            ->pluck('order')
            ->filter(static fn ($order) => $order !== null && $order !== '')
            ->map(static fn ($order) => (int) $order)
            ->values();

        if ($orders->count() !== $orders->unique()->count()) {
            $v->errors()->add('installments', __('guarantor.installment_order_duplicate'));

            return false;
        }

        $sorted = $orders->sort()->values()->all();
        $expected = range(1, count($sorted));

        if ($sorted !== $expected) {
            $v->errors()->add('installments', __('guarantor.installment_order_not_sequential'));

            return false;
        }

        return true;
    }

    /**
     * @param  list<array<string, mixed>>  $installments
     */
    private function validateInstallmentSum(Validator $v, array $installments): void
    {
        $total = collect($installments)->sum('amount');
        $expected = (float) $this->input('total_amount', 0);

        if (round($total, 2) !== round($expected, 2)) {
            $v->errors()->add(
                'installments',
                __('guarantor.installments_sum_mismatch')
            );
        }
    }

    /**
     * @param  list<array<string, mixed>>  $installments
     */
    private function validateFirstInstallmentMaxDays(Validator $v, array $installments): void
    {
        $map = $this->buildInstallmentOrderMap($installments);

        if (! isset($map[1])) {
            return;
        }

        $maxDays = (int) app('settings')->get('guarantor_first_installment_max_days', 5);
        $latestAllowed = now()->startOfDay()->addDays($maxDays);

        try {
            $firstDue = Carbon::parse($map[1]['due_date'])->startOfDay();
        } catch (\Exception) {
            return;
        }

        if ($firstDue->gt($latestAllowed)) {
            $v->errors()->add(
                "installments.{$map[1]['index']}.due_date",
                __('guarantor.installment_due_date_first_within_days', ['days' => $maxDays])
            );
        }
    }

    /**
     * @param  list<array<string, mixed>>  $installments
     */
    private function validateInstallmentChronologicalOrder(Validator $v, array $installments): void
    {
        $map = $this->buildInstallmentOrderMap($installments);
        $maxOrder = count($map);

        for ($order = 2; $order <= $maxOrder; $order++) {
            if (! isset($map[$order], $map[$order - 1])) {
                continue;
            }

            try {
                $current = Carbon::parse($map[$order]['due_date'])->startOfDay();
                $previous = Carbon::parse($map[$order - 1]['due_date'])->startOfDay();
            } catch (\Exception) {
                continue;
            }

            if ($current->lt($previous)) {
                $v->errors()->add(
                    "installments.{$map[$order]['index']}.due_date",
                    __('guarantor.installment_due_date_before_previous', ['order' => $order])
                );
            }
        }
    }

    /**
     * @param  list<array<string, mixed>>  $installments
     * @return array<int, array{due_date: string, index: int}>
     */
    private function buildInstallmentOrderMap(array $installments): array
    {
        $map = [];

        foreach ($installments as $index => $installment) {
            if (! is_array($installment)) {
                continue;
            }

            $order = $installment['order'] ?? null;
            $dueDate = $installment['due_date'] ?? null;

            if ($order === null || $order === '' || $dueDate === null || $dueDate === '') {
                continue;
            }

            $map[(int) $order] = [
                'due_date' => (string) $dueDate,
                'index' => $index,
            ];
        }

        return $map;
    }
}
