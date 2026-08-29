<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('setting_histories', function (Blueprint $table) {
            $table->string('actor_name')->nullable()->after('admin_id');
        });
    }

    public function down(): void
    {
        Schema::table('setting_histories', function (Blueprint $table) {
            $table->dropColumn('actor_name');
        });
    }
};
