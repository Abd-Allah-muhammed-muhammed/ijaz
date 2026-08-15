<?php

use Illuminate\Database\Migrations\Migration;
use Modules\Geo\Actions\Nationality\CorrectSwappedHiUrNationalityTranslationsAction;

/**
 * One-time data correction: Arabic admin UI labels for "name in hi" / "name in ur"
 * were reversed, so 29 nationalities stored Urdu-script text on locale=hi and
 * Devanagari on locale=ur. Filipino (id 33) is a separate mismatch (Urdu in hi,
 * English "Filipino" in ur).
 *
 * Idempotent — safe to run twice. Does not touch ar/en or ids 1, 2, 17.
 * Irreversible as a schema rollback; re-running up() will not swap back.
 */
return new class extends Migration
{
    public function up(): void
    {
        app(CorrectSwappedHiUrNationalityTranslationsAction::class)->handle();
    }

    public function down(): void
    {
        // Irreversible data correction — see class docblock.
    }
};
