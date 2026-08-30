<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', static function (Blueprint $table) {
            $table->json('composed_of_slugs')->nullable()->after('slug');
        });
    }

    public function down(): void
    {
        Schema::table('pages', static function (Blueprint $table) {
            $table->dropColumn('composed_of_slugs');
        });
    }
};
