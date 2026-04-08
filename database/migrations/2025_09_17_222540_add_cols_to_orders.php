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
        Schema::table('orders', static function (Blueprint $table) {
            $table->decimal('user_fees', 10, 2)->default(0);
            $table->decimal('provider_fees', 10, 2)->default(0);
            $table->decimal('total_fees', 10, 2)->storedAs('user_fees + provider_fees');
            $table->decimal('user_total', 10, 2)->storedAs('price + user_fees');
            $table->decimal('provider_total', 10, 2)->storedAs('price - provider_fees');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', static function (Blueprint $table) {
            $table->dropColumn(['user_fees', 'provider_fees', 'total_fees', 'user_total', 'provider_total']);
        });
    }
};
