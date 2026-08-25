<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payout_requests', function (Blueprint $table) {
            $table->dropIndex('payout_requests_operation_type_operation_id_index');
            $table->unique(['operation_type', 'operation_id'], 'payout_requests_operation_unique');
        });
    }

    public function down(): void
    {
        Schema::table('payout_requests', function (Blueprint $table) {
            $table->dropUnique('payout_requests_operation_unique');
            $table->index(['operation_type', 'operation_id'], 'payout_requests_operation_type_operation_id_index');
        });
    }
};
