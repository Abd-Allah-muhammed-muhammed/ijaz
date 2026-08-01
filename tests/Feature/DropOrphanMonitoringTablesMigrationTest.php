<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

it('drops orphan pulse and telescope tables from the default connection only', function (): void {
    $default = (string) config('database.default');
    $monitoring = 'monitoring';

    expect($default)->not->toBe($monitoring);

    $tables = [
        'telescope_entries_tags',
        'telescope_entries',
        'telescope_monitoring',
        'pulse_aggregates',
        'pulse_entries',
        'pulse_values',
    ];

    $defaultSchema = Schema::connection($default);
    $monitoringSchema = Schema::connection($monitoring);

    foreach ($tables as $table) {
        if (! $defaultSchema->hasTable($table)) {
            $defaultSchema->create($table, function (Blueprint $blueprint): void {
                $blueprint->id();
            });
        }

        if (! $monitoringSchema->hasTable($table)) {
            $monitoringSchema->create($table, function (Blueprint $blueprint): void {
                $blueprint->id();
            });
        }
    }

    foreach ($tables as $table) {
        expect($defaultSchema->hasTable($table))->toBeTrue();
        expect($monitoringSchema->hasTable($table))->toBeTrue();
    }

    $migration = require database_path('migrations/2026_08_01_001421_drop_orphan_pulse_and_telescope_tables_from_default_connection.php');
    $migration->up();

    foreach ($tables as $table) {
        expect($defaultSchema->hasTable($table))->toBeFalse();
        expect($monitoringSchema->hasTable($table))->toBeTrue();
    }
});
