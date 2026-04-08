<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropIndex('reviews_operation_type_operation_id_index');
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->char('operation_id', 36)->change();
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->index(['operation_type', 'operation_id'], 'reviews_operation_type_operation_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropIndex('reviews_operation_type_operation_id_index');
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->unsignedBigInteger('operation_id')->change();
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->index(['operation_type', 'operation_id'], 'reviews_operation_type_operation_id_index');
        });
    }
};
