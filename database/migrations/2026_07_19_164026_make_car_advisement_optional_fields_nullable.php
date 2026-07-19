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
        Schema::table('car_advisements', static function (Blueprint $table) {
            $table->unsignedBigInteger('car_category_id')->nullable()->change();
            $table->integer('mileage')->nullable()->change();
            $table->string('transmission')->nullable()->change();
            $table->string('fuel_type')->nullable()->change();
            $table->string('color')->nullable()->change();
            $table->string('phone')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('car_advisements', static function (Blueprint $table) {
            $table->unsignedBigInteger('car_category_id')->nullable(false)->change();
            $table->integer('mileage')->nullable(false)->change();
            $table->string('transmission')->nullable(false)->change();
            $table->string('fuel_type')->nullable(false)->change();
            $table->string('color')->nullable(false)->change();
            $table->string('phone')->nullable(false)->change();
        });
    }
};
