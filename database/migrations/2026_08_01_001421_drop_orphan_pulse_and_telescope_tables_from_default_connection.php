<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Drop Pulse/Telescope tables left on the main application database before the
 * shared `monitoring` connection was introduced. Never touches `monitoring`.
 */
return new class extends Migration
{
    /**
     * @var list<string>
     */
    private array $tables = [
        'telescope_entries_tags',
        'telescope_entries',
        'telescope_monitoring',
        'pulse_aggregates',
        'pulse_entries',
        'pulse_values',
    ];

    public function up(): void
    {
        $default = (string) config('database.default');

        if ($default === 'monitoring') {
            return;
        }

        $schema = Schema::connection($default);

        foreach ($this->tables as $table) {
            $schema->dropIfExists($table);
        }
    }

    public function down(): void
    {
        // Irreversible one-time cleanup — recreate via package migrations on the
        // monitoring connection if needed: php artisan migrate --database=monitoring
    }
};
