<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->index('status', 'payments_status_index');
            $table->index('created_at', 'payments_created_at_index');
            $table->index(
                ['user_type', 'user_id', 'created_at'],
                'payments_user_created_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('payments_status_index');
            $table->dropIndex('payments_created_at_index');
            $table->dropIndex('payments_user_created_index');
        });
    }
};
