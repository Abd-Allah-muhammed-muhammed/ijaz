<?php

/**
 * Permanent CI safeguard: seeders must never raw-insert into tables that rely on
 * HasNormalizedAttributes / translation saving hooks for normalized_* columns.
 *
 * If this fails, convert the seeder to Eloquent model::create() so normalized_*
 * is populated automatically.
 */
test('seeders must not raw-insert into normalized_* bearing tables', function (): void {
    $forbiddenTables = [
        'category_translations',
        'skill_translations',
        'region_translations',
        'city_translations',
        'nationality_translations',
        'property_category_translations',
        'car_category_translations',
        'device_category_translations',
        'electronic_brand_translations',
        'specialization_translations',
        'property_advisements',
        'car_advisements',
        'electronic_advisements',
        'institute_advisements',
    ];

    $seederRoots = [
        database_path('seeders'),
        base_path('Modules'),
    ];

    $violations = [];

    foreach ($seederRoots as $root) {
        if (! is_dir($root)) {
            continue;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if (! $file->isFile() || ! str_ends_with($file->getFilename(), 'Seeder.php')) {
                continue;
            }

            $contents = file_get_contents($file->getPathname());
            if ($contents === false) {
                continue;
            }

            foreach ($forbiddenTables as $table) {
                if (preg_match("/DB::table\\(\\s*['\"]".preg_quote($table, '/')."['\"]\\s*\\)/", $contents) === 1) {
                    $violations[] = $file->getPathname().' → DB::table(\''.$table.'\')';
                }
            }
        }
    }

    expect($violations)->toBeEmpty(
        "Raw DB::table() inserts into normalized_*-bearing tables are forbidden.\n".
        "Use Eloquent models so HasNormalizedAttributes / translation saving hooks run.\n".
        implode("\n", $violations)
    );
});
