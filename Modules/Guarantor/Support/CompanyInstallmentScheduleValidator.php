<?php

namespace Modules\Guarantor\Support;

use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;

class CompanyInstallmentScheduleValidator
{
    /**
     * @param  list<array<string, mixed>>  $installments
     * @return list<array{field: string, message: string}>
     */
    public function validate(array $installments, float $totalAmount): array
    {
        if ($installments === []) {
            return [];
        }

        $errors = [];

        $orderValid = $this->validateUniqueSequentialOrder($installments, $errors);

        $this->validateInstallmentSum($installments, $totalAmount, $errors);

        if (! $orderValid) {
            return $errors;
        }

        $orderMap = $this->buildInstallmentOrderMap($installments);

        $this->validateFirstInstallmentMaxDays($orderMap, $errors);
        $this->validateInstallmentChronologicalOrder($orderMap, $errors);

        return $errors;
    }

    /**
     * @param  list<array<string, mixed>>  $installments
     * @param  list<array{field: string, message: string}>  $errors
     */
    private function validateUniqueSequentialOrder(array $installments, array &$errors): bool
    {
        $orders = collect($installments)
            ->pluck('order')
            ->filter(static fn ($order) => $order !== null && $order !== '')
            ->map(static fn ($order) => (int) $order)
            ->values();

        if ($orders->count() !== $orders->unique()->count()) {
            $errors[] = [
                'field' => 'installments',
                'message' => __('guarantor.installment_order_duplicate'),
            ];

            return false;
        }

        $sorted = $orders->sort()->values()->all();
        $expected = range(1, count($sorted));

        if ($sorted !== $expected) {
            $errors[] = [
                'field' => 'installments',
                'message' => __('guarantor.installment_order_not_sequential'),
            ];

            return false;
        }

        return true;
    }

    /**
     * @param  list<array<string, mixed>>  $installments
     * @param  list<array{field: string, message: string}>  $errors
     */
    private function validateInstallmentSum(array $installments, float $totalAmount, array &$errors): void
    {
        $total = collect($installments)->sum('amount');

        if (round($total, 2) !== round($totalAmount, 2)) {
            $errors[] = [
                'field' => 'installments',
                'message' => __('guarantor.installments_sum_mismatch'),
            ];
        }
    }

    /**
     * @param  array<int, array{due_date: string, index: int}>  $orderMap
     * @param  list<array{field: string, message: string}>  $errors
     */
    private function validateFirstInstallmentMaxDays(array $orderMap, array &$errors): void
    {
        if (! isset($orderMap[1])) {
            return;
        }

        $field = "installments.{$orderMap[1]['index']}.due_date";
        $firstDue = $this->parseDueDate($orderMap[1]['due_date'], $field, $errors);

        if ($firstDue === null) {
            return;
        }

        $maxDays = (int) app('settings')->get('guarantor_first_installment_max_days', 5);
        $latestAllowed = now()->startOfDay()->addDays($maxDays);

        if ($firstDue->gt($latestAllowed)) {
            $errors[] = [
                'field' => $field,
                'message' => __('guarantor.installment_due_date_first_within_days', ['days' => $maxDays]),
            ];
        }
    }

    /**
     * @param  array<int, array{due_date: string, index: int}>  $orderMap
     * @param  list<array{field: string, message: string}>  $errors
     */
    private function validateInstallmentChronologicalOrder(array $orderMap, array &$errors): void
    {
        $maxOrder = count($orderMap);

        for ($order = 2; $order <= $maxOrder; $order++) {
            if (! isset($orderMap[$order], $orderMap[$order - 1])) {
                continue;
            }

            $previousField = "installments.{$orderMap[$order - 1]['index']}.due_date";
            $currentField = "installments.{$orderMap[$order]['index']}.due_date";

            $previous = $this->parseDueDate($orderMap[$order - 1]['due_date'], $previousField, $errors);

            if ($previous === null) {
                continue;
            }

            $current = $this->parseDueDate($orderMap[$order]['due_date'], $currentField, $errors);

            if ($current === null) {
                continue;
            }

            if ($current->lt($previous)) {
                $errors[] = [
                    'field' => $currentField,
                    'message' => __('guarantor.installment_due_date_before_previous', ['order' => $order]),
                ];
            }
        }
    }

    /**
     * @param  list<array<string, mixed>>  $installments
     * @return array<int, array{due_date: string, index: int}>
     */
    protected function buildInstallmentOrderMap(array $installments): array
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

    /**
     * @param  list<array{field: string, message: string}>  $errors
     */
    private function parseDueDate(string $dueDate, string $field, array &$errors): ?Carbon
    {
        try {
            return Carbon::parse($dueDate)->startOfDay();
        } catch (InvalidFormatException) {
            $errors[] = [
                'field' => $field,
                'message' => __('guarantor.installment_due_date_invalid'),
            ];

            return null;
        }
    }
}
