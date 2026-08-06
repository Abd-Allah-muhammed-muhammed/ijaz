<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Legacy chat attachment storage retired — MediaLibrary on ConversationMessage
     * is the sole path (migration verified; remaining legacy rows had missing files).
     */
    public function up(): void
    {
        Schema::dropIfExists('conversation_attachments');
    }

    public function down(): void
    {
        // Intentionally empty — legacy table is not restored. Recreate from
        // 2024_10_20_152133_create_conversation_attachments_table if ever needed.
    }
};
