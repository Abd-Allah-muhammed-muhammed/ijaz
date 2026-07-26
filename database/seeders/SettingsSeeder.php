<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Settings\Models\Setting;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = collect([
            ['key' => 'youtube', 'content' => '', 'group' => 'general'],
            ['key' => 'facebook', 'content' => '', 'group' => 'general'],
            ['key' => 'whatsapp', 'content' => '966500000000', 'group' => 'general'],
            ['key' => 'x', 'content' => '', 'group' => 'general'],
            ['key' => 'instagram', 'content' => '', 'group' => 'general'],
            ['key' => 'tiktok', 'content' => '', 'group' => 'general'],
            ['key' => 'snapchat', 'content' => '', 'group' => 'general'],
            ['key' => 'telegram', 'content' => '', 'group' => 'general'],
            ['key' => 'phone', 'content' => '966500000000', 'group' => 'general'],
            ['key' => 'email', 'content' => 'info@ijaz.sa', 'group' => 'general'],
            ['key' => 'offer_note', 'content' => 'Special offer: Get a 10% discount on your first service booking! Use code FIRST10 at checkout.', 'group' => 'general'],
            ['key' => 'guarantee_notes', 'content' => 'The guarantee fee is a refundable amount held to ensure the successful completion of the service. It is returned to the user upon satisfactory completion of the service as per the agreed terms.', 'group' => 'guarantor'],
            ['key' => 'guarantee_fee', 'content' => '20', 'group' => 'guarantor'],
            ['key' => 'chat_notes', 'content' => 'Please be respectful and professional in your communication. Avoid sharing personal information and adhere to our community guidelines.', 'group' => 'chat'],
            ['key' => 'provider_registration_bonus_enabled', 'content' => '1', 'group' => 'wallet'],
            ['key' => 'provider_registration_bonus_amount', 'content' => '50', 'group' => 'wallet'],
            ['key' => 'min_withdraw_amount', 'content' => '200', 'group' => 'wallet'],
        ]);

        $settings->each(function (array $setting): void {
            Setting::query()->updateOrCreate(
                ['key' => $setting['key']],
                [
                    'content' => $setting['content'],
                    'group' => $setting['group'],
                ],
            );
        });

        cache()->forget('settings');
        app()->forgetInstance('settings');
    }
}
