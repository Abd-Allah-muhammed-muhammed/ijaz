<?php

namespace App\Actions\PanAnalytics;

/**
 * Single source of truth for pan analytics element categories.
 *
 * PHP categorization (summary / export / resource) and SQL LIKE filters
 * (paginated index) both read from here so taxonomy cannot drift.
 *
 * SQL filters stay in the repository for correct pagination on large tables;
 * this Action owns the pattern lists those filters apply.
 */
class CategorizePanElementAction
{
    /**
     * @return list<string>
     */
    public function patternsFor(string $category): array
    {
        return match ($category) {
            'page' => ['%-page', '%page%'],
            'button' => ['%-btn', '%-button', '%button%'],
            'form' => ['%step%', '%form%', '%input%', '%checkbox%', '%field%'],
            default => [],
        };
    }

    public function handle(string $name): string
    {
        if (str_ends_with($name, '-page') || str_contains($name, 'page')) {
            return 'page';
        }

        if (str_ends_with($name, '-btn') || str_ends_with($name, '-button') || str_contains($name, 'button')) {
            return 'button';
        }

        if (str_contains($name, 'step') ||
            str_contains($name, 'form') ||
            str_contains($name, 'input') ||
            str_contains($name, 'checkbox') ||
            str_contains($name, 'field')) {
            return 'form';
        }

        return 'other';
    }
}
