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
        if (! Schema::hasTable('guarantee_requests')) {
            return;
        }

        // Delete related data first
        if (Schema::hasTable('media')) {
            DB::table('media')->where('model_type', 'App\\Models\\GuaranteeRequest')->delete();
        }

        if (Schema::hasTable('conversation_messages') && Schema::hasTable('conversations')) {
            DB::table('conversation_messages')
                ->whereIn('conversation_id', function ($query) {
                    $query->select('id')->from('conversations')
                        ->where('operation_type', 'App\\Models\\GuaranteeRequest');
                })->delete();

            DB::table('conversations')->where('operation_type', 'App\\Models\\GuaranteeRequest')->delete();
        }

        // Truncate guarantee_requests
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            DB::table('guarantee_requests')->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        } else {
            DB::table('guarantee_requests')->delete();
        }

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
        if (! Schema::hasTable('guarantee_requests')) {
            return;
        }

        Schema::table('guarantee_requests', function (Blueprint $table) {
            $table->dropColumn('id');
        });

        Schema::table('guarantee_requests', function (Blueprint $table) {
            $table->bigIncrements('id')->first();
        });
    }
};
