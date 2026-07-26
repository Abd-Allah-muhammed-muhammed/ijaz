<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Keys previously listed in Modules/Settings/config/settings.php public_keys.
     * Must stay public after this migration to preserve exact API exposure.
     *
     * @var list<string>
     */
    private const PREVIOUSLY_PUBLIC_KEYS = [
        'youtube',
        'facebook',
        'whatsapp',
        'x',
        'instagram',
        'tiktok',
        'snapchat',
        'telegram',
        'phone',
        'email',
        'offer_note',
        'guarantee_notes',
        'guarantee_fee',
        'chat_notes',
        'provider_registration_bonus_enabled',
        'provider_registration_bonus_amount',
        'min_withdraw_amount',
    ];

    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->boolean('is_public')->default(false)->after('group');
        });

        DB::table('settings')
            ->whereIn('key', self::PREVIOUSLY_PUBLIC_KEYS)
            ->update(['is_public' => true]);
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('is_public');
        });
    }
};
