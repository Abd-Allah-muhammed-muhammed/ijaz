<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Delete related data first
        DB::table('media')->where('model_type', 'App\\Models\\GuaranteeRequest')->delete();

        DB::table('conversation_messages')
            ->whereIn('conversation_id', function ($query) {
                $query->select('id')->from('conversations')
                    ->where('operation_type', 'App\\Models\\GuaranteeRequest');
            })->delete();

        DB::table('conversations')->where('operation_type', 'App\\Models\\GuaranteeRequest')->delete();

        // Truncate guarantee_requests
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('guarantee_requests')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Modify the table structure
        Schema::table('guarantee_requests', function (Blueprint $table) {
            $table->dropColumn('id');
        });

        Schema::table('guarantee_requests', function (Blueprint $table) {
            $table->uuid('id')->primary()->first();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('guarantee_requests', function (Blueprint $table) {
            $table->dropColumn('id');
        });

        Schema::table('guarantee_requests', function (Blueprint $table) {
            $table->bigIncrements('id')->first();
        });
    }
};
