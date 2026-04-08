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
        Schema::table('top_up_requests', static function (Blueprint $table) {
            $table->string('payment_driver')->nullable()->after('transaction_id');
            $table->string('payment_status')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('top_up_requests', static function (Blueprint $table) {
            $table->dropColumn('payment_driver');
        });
    }
};
