<?php

/**
 * Regression: Admins / Reviews Index tables previously rendered created_at
 * with no `render`, so the shared Table printed the raw ISO 8601 string.
 * Fix: formatDate() from @/shared/lib/formatters (not legacy build_date).
 */
test('Admins table renders created_at as a formatted date, not raw ISO', function (): void {
    $path = resource_path('js/apps/admin/pages/Admins/Index.tsx');
    expect(file_exists($path))->toBeTrue();

    $source = (string) file_get_contents($path);

    expect($source)->toContain("from '@/shared/lib/formatters'")
        ->and($source)->toContain('formatDate')
        ->and($source)->toMatch("/property:\s*'created_at',\s*render:\s*\(row\)\s*=>\s*formatDate\(row\.created_at\)/")
        ->and($source)->not->toContain('build_date');
});

test('Reviews table renders created_at as a formatted date, not raw ISO', function (): void {
    $path = resource_path('js/apps/admin/pages/Reviews/Index.tsx');
    expect(file_exists($path))->toBeTrue();

    $source = (string) file_get_contents($path);

    expect($source)->toContain("from '@/shared/lib/formatters'")
        ->and($source)->toContain('formatDate')
        ->and($source)->toMatch("/property:\s*'created_at',\s*render:\s*\(row\)\s*=>\s*formatDate\(row\.created_at\)/")
        ->and($source)->not->toContain('build_date');
});
