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
        Schema::table('property_advisements', static function (Blueprint $table) {
            $table->unsignedBigInteger('category_id')->nullable()->change();
            $table->integer('area')->nullable()->change();
            $table->integer('bedrooms_count')->nullable()->change();
            $table->integer('bathrooms_count')->nullable()->change();
            $table->integer('halls_count')->nullable()->change();
            $table->integer('age')->nullable()->change();
            $table->string('facade')->nullable()->change();
            $table->string('street_width')->nullable()->change();
            $table->string('street_type')->nullable()->change();
            $table->string('phone')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('property_advisements', static function (Blueprint $table) {
            $table->unsignedBigInteger('category_id')->nullable(false)->change();
            $table->integer('area')->nullable(false)->change();
            $table->integer('bedrooms_count')->nullable(false)->change();
            $table->integer('bathrooms_count')->nullable(false)->change();
            $table->integer('halls_count')->nullable(false)->change();
            $table->integer('age')->nullable(false)->change();
            $table->string('facade')->nullable(false)->change();
            $table->string('street_width')->nullable(false)->change();
            $table->string('street_type')->nullable(false)->change();
            $table->string('phone')->nullable(false)->change();
        });
    }
};
