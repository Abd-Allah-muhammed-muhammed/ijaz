<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = collect([
            ['key' => 'youtube', 'content' => ''],
            ['key' => 'facebook', 'content' => ''],
            ['key' => 'whatsapp', 'content' => '966500000000'],
            ['key' => 'x', 'content' => ''],
            ['key' => 'instagram', 'content' => ''],
            ['key' => 'tiktok', 'content' => ''],
            ['key' => 'snapchat', 'content' => ''],
            ['key' => 'telegram', 'content' => ''],
            ['key' => 'phone', 'content' => '966500000000'],
            ['key' => 'email', 'content' => 'info@ijaz.sa'],
            ['key' => 'guarantee_notes', 'content' => 'The guarantee fee is a refundable amount held to ensure the successful completion of the service. It is returned to the user upon satisfactory completion of the service as per the agreed terms.'],
            ['key' => 'offer_note', 'content' => 'Special offer: Get a 10% discount on your first service booking! Use code FIRST10 at checkout.'],
            ['key' => 'guarantee_fee', 'content' => '20'],
            ['key' => 'chat_notes', 'content' => 'Please be respectful and professional in your communication. Avoid sharing personal information and adhere to our community guidelines.'],
            ['key' => 'provider_registration_bonus_enabled', 'content' => '1'],
            ['key' => 'provider_registration_bonus_amount', 'content' => '50'],
            ['key' => 'min_withdraw_amount', 'content' => '200'],
        ]);
        $settings->each(fn ($setting) => Setting::firstOrCreate(['key' => $setting['key']], $setting));
        cache()->forget('settings');
    }
}
