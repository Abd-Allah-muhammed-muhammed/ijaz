<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guarantor_status_histories', function (Blueprint $table) {
            $table->string('actor_name')->nullable()->after('actor_type');
        });
    }

    public function down(): void
    {
        Schema::table('guarantor_status_histories', function (Blueprint $table) {
            $table->dropColumn('actor_name');
        });
    }
};
