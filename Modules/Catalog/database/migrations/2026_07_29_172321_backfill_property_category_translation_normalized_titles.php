<?php

use App\Support\Normalize;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * One-time data fix: PropertyCategoryTranslation historically never wrote
 * normalized_title on save, so Dashboard/API search matched nothing.
 * New rows are populated by the model saving hook; this backfills existing NULLs.
 *
 * Intentionally irreversible — rolling back would re-null search indexes.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('property_category_translations')
            ->whereNull('normalized_title')
            ->orderBy('id')
            ->each(function (object $row): void {
                DB::table('property_category_translations')
                    ->where('id', $row->id)
                    ->update([
                        'normalized_title' => Normalize::make($row->title, $row->locale)->toString(),
                    ]);
            });
    }

    public function down(): void
    {
        // Irreversible data backfill — see class docblock.
    }
};
