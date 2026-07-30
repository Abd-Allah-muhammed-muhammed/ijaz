<?php

use App\Support\Normalize;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * One-time data fix: Region/City/Nationality translations seeded via raw DB inserts
 * never wrote normalized_* columns, so Dashboard search against those columns matched
 * nothing. New rows are populated by HasNormalizedAttributes; this backfills existing NULLs.
 *
 * Intentionally irreversible — rolling back would re-null search indexes.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('region_translations')
            ->where(function ($query): void {
                $query->whereNull('normalized_title')->orWhere('normalized_title', '');
            })
            ->orderBy('id')
            ->each(function (object $row): void {
                DB::table('region_translations')
                    ->where('id', $row->id)
                    ->update([
                        'normalized_title' => Normalize::make($row->title, $row->locale)->toString(),
                    ]);
            });

        DB::table('city_translations')
            ->where(function ($query): void {
                $query->whereNull('normalized_title')->orWhere('normalized_title', '');
            })
            ->orderBy('id')
            ->each(function (object $row): void {
                DB::table('city_translations')
                    ->where('id', $row->id)
                    ->update([
                        'normalized_title' => Normalize::make($row->title, $row->locale)->toString(),
                    ]);
            });

        DB::table('nationality_translations')
            ->where(function ($query): void {
                $query->whereNull('normalized_name')->orWhere('normalized_name', '');
            })
            ->orderBy('id')
            ->each(function (object $row): void {
                DB::table('nationality_translations')
                    ->where('id', $row->id)
                    ->update([
                        'normalized_name' => Normalize::make($row->name, $row->locale)->toString(),
                    ]);
            });
    }

    public function down(): void
    {
        // Irreversible data backfill — see class docblock.
    }
};
