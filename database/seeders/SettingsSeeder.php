<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Settings\Models\Setting;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * is_public mirrors the former config/settings.php public_keys allowlist
     * (every seeded key was public).
     */
    public function run(): void
    {
        $settings = collect([
            ['key' => 'youtube', 'content' => '', 'group' => 'general', 'is_public' => true],
            ['key' => 'facebook', 'content' => '', 'group' => 'general', 'is_public' => true],
            ['key' => 'whatsapp', 'content' => '966500000000', 'group' => 'general', 'is_public' => true],
            ['key' => 'x', 'content' => '', 'group' => 'general', 'is_public' => true],
            ['key' => 'instagram', 'content' => '', 'group' => 'general', 'is_public' => true],
            ['key' => 'tiktok', 'content' => '', 'group' => 'general', 'is_public' => true],
            ['key' => 'snapchat', 'content' => '', 'group' => 'general', 'is_public' => true],
            ['key' => 'telegram', 'content' => '', 'group' => 'general', 'is_public' => true],
            ['key' => 'phone', 'content' => '966500000000', 'group' => 'general', 'is_public' => true],
            ['key' => 'email', 'content' => 'info@ijaz.sa', 'group' => 'general', 'is_public' => true],
            ['key' => 'offer_note', 'content' => 'Special offer: Get a 10% discount on your first service booking! Use code FIRST10 at checkout.', 'group' => 'general', 'is_public' => true],
            ['key' => 'guarantee_notes', 'content' => 'The guarantee fee is a refundable amount held to ensure the successful completion of the service. It is returned to the user upon satisfactory completion of the service as per the agreed terms.', 'group' => 'guarantor', 'is_public' => true],
            ['key' => 'guarantee_fee', 'content' => '20', 'group' => 'guarantor', 'is_public' => true],
            ['key' => 'chat_notes', 'content' => 'Please be respectful and professional in your communication. Avoid sharing personal information and adhere to our community guidelines.', 'group' => 'chat', 'is_public' => true],
            ['key' => 'provider_registration_bonus_enabled', 'content' => '1', 'group' => 'wallet', 'is_public' => true],
            ['key' => 'provider_registration_bonus_amount', 'content' => '50', 'group' => 'wallet', 'is_public' => true],
            ['key' => 'min_withdraw_amount', 'content' => '200', 'group' => 'wallet', 'is_public' => true],
        ]);

        $settings->each(function (array $setting): void {
            Setting::query()->updateOrCreate(
                ['key' => $setting['key']],
                [
                    'content' => $setting['content'],
                    'group' => $setting['group'],
                    'is_public' => $setting['is_public'],
                ],
            );
        });

        cache()->forget('settings');
        app()->forgetInstance('settings');
    }
}
