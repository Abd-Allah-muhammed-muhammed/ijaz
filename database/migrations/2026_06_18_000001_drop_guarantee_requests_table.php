<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('guarantee_requests');
    }

    public function down(): void
    {
        // Intentionally empty — data is gone
    }
};
